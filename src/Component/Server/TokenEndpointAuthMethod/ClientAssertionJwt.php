<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Server\TokenEndpointAuthMethod;

use Assert\Assertion;
use Jose\Component\Checker\ClaimCheckerManager;
use Jose\Component\Core\JWKSet;
use Jose\Component\Encryption\JWELoader;
use Jose\Component\Signature\JWSLoader;
use OAuth2Framework\Component\Server\Model\Client\Client;
use OAuth2Framework\Component\Server\Model\Client\ClientId;
use OAuth2Framework\Component\Server\Model\DataBag\DataBag;
use OAuth2Framework\Component\Server\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\Response\OAuth2ResponseFactoryManager;
use Psr\Http\Message\ServerRequestInterface;

abstract class ClientAssertionJwt implements TokenEndpointAuthMethodInterface
{
    /**
     * @var JWSLoader
     */
    private $jwsLoader;

    /**
     * @var JWELoader
     */
    private $jweLoader;

    /**
     * @var bool
     */
    private $encryptionRequired = false;

    /**
     * @var JWKSet|null
     */
    private $keyEncryptionKeySet = null;

    /**
     * @var int
     */
    private $secretLifetime;

    /**
     * @var ClaimCheckerManager
     */
    private $claimCheckerManager;

    /**
     * ClientAssertionJwt constructor.
     *
     * @param JWSLoader $jwsLoader
     * @param int       $secretLifetime
     */
    public function __construct(JWSLoader $jwsLoader, ClaimCheckerManager $claimCheckerManager, int $secretLifetime = 0)
    {
        Assertion::greaterOrEqualThan($secretLifetime, 0);
        $this->jwsLoader = $jwsLoader;
        $this->claimCheckerManager = $claimCheckerManager;
        $this->secretLifetime = $secretLifetime;
    }

    /**
     * @param bool                         $encryptionRequired
     * @param JWKSet $keyEncryptionKeySet
     */
    public function enableEncryptedAssertions(bool $encryptionRequired, JWKSet $keyEncryptionKeySet)
    {
        $this->encryptionRequired = $encryptionRequired;
        $this->keyEncryptionKeySet = $keyEncryptionKeySet;
    }

    /**
     * @return string[]
     */
    public function getSupportedSignatureAlgorithms(): array
    {
        return $this->jwsLoader->getSupportedSignatureAlgorithms();
    }

    /**
     * @return string[]
     */
    public function getSupportedContentEncryptionAlgorithms(): array
    {
        return $this->jweLoader->getSupportedContentEncryptionAlgorithms();
    }

    /**
     * @return string[]
     */
    public function getSupportedKeyEncryptionAlgorithms(): array
    {
        return $this->jweLoader->getSupportedKeyEncryptionAlgorithms();
    }

    /**
     * {@inheritdoc}
     */
    public function getSchemesParameters(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function findClientId(ServerRequestInterface $request, &$clientCredentials = null): ? ClientId
    {
        $parameters = $request->getParsedBody() ?? [];
        if (!array_key_exists('client_assertion_type', $parameters)) {
            return null;
        }
        $clientAssertionType = $parameters['client_assertion_type'];

        //We verify the client assertion type in the request
        if ('urn:ietf:params:oauth:client-assertion-type:jwt-bearer' !== $clientAssertionType) {
            return null;
        }

        try {
            Assertion::keyExists($parameters, 'client_assertion', 'Parameter \'client_assertion\' is missing.');
            $client_assertion = $parameters['client_assertion'];
            $client_assertion = $this->tryToDecryptClientAssertion($client_assertion);
            $jwt = $this->jwsLoader->load($client_assertion);
            $this->claimCheckerManager->check($jwt);
            $claims = json_decode($jwt->getPayload(), true);

            $diff = array_diff(['iss', 'sub', 'aud', 'jti', 'exp'], array_keys($claims));
            Assertion::eq(0, count($diff), sprintf('The following claim(s) is/are mandatory: \'%s\'.', implode(', ', array_values($diff))));
            Assertion::eq($claims['sub'], $claims['iss'], 'The claims \'sub\' and \'iss\' must contain the client public ID.');
        } catch (\Exception $e) {
            throw new OAuth2Exception(400, ['error' => OAuth2ResponseFactoryManager::ERROR_INVALID_REQUEST, 'error_description' => $e->getMessage()]);
        }

        $clientCredentials = $jwt;

        return ClientId::create($claims['sub']);
    }

    /**
     * {@inheritdoc}
     */
    private function tryToDecryptClientAssertion(string $clientAssertion): string
    {
        if (null === $this->jweLoader) {
            return $clientAssertion;
        }

        try {
            $jwe = $this->jweLoader->load($clientAssertion);
            $jwe = $this->jweLoader->decryptUsingKeySet($jwe, $this->keyEncryptionKeySet);

            return $jwe->getPayload();
        } catch (\Exception $e) {
            if (true === $this->encryptionRequired) {
                throw new OAuth2Exception(400, ['error' => OAuth2ResponseFactoryManager::ERROR_INVALID_REQUEST, 'error_description' => $e->getMessage()]);
            }

            return $clientAssertion;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isClientAuthenticated(Client $client, $clientCredentials, ServerRequestInterface $request): bool
    {
        try {
            $jwkSet = $client->getPublicKeySet();
            Assertion::isInstanceOf($jwkSet, JWKSet::class);
            $this->jwsLoader->verifyWithKeySet($clientCredentials, $jwkSet);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedAuthenticationMethods(): array
    {
        return ['client_secret_jwt', 'private_key_jwt'];
    }

    /**
     * {@inheritdoc}
     */
    public function checkClientConfiguration(DataBag $commandParameters, DataBag $validatedParameters): DataBag
    {
        if ('client_secret_jwt' === $commandParameters->get('token_endpoint_auth_method')) {
            $validatedParameters = $validatedParameters->with('client_secret', $this->createClientSecret());
            $validatedParameters = $validatedParameters->with('client_secret_expires_at', (0 === $this->secretLifetime ? 0 : time() + $this->secretLifetime));
        } elseif ('private_key_jwt' === $commandParameters->get('token_endpoint_auth_method')) {
            Assertion::true($commandParameters->has('jwks') xor $commandParameters->has('jwks_uri'), 'The parameter \'jwks\' or \'jwks_uri\' must be set.');
            if ($commandParameters->has('jwks')) {
                $jwks = JWKSet::createFromKeyData($commandParameters->get('jwks'));
                Assertion::isInstanceOf($jwks, JWKSet::class, 'The parameter \'jwks\' must be a valid JWKSet object.');
                $validatedParameters = $validatedParameters->with('jwks', $commandParameters->get('jwks'));
            }/* else {
                $jwks = JWKFactory::createFromJKU($commandParameters->get('jwks_uri'));
                Assertion::isInstanceOf($jwks, JWKSet::class, 'The parameter \'jwks_uri\' must be a valid uri that provide a valid JWKSet.');
                $validatedParameters = $validatedParameters->with('jwks_uri', $commandParameters->get('jwks_uri'));
            }*/
        } else {
            throw new \InvalidArgumentException('Unsupported token endpoint authentication method.');
        }

        return $validatedParameters;
    }

    /**
     * @return string
     */
    abstract protected function createClientSecret(): string;
}
