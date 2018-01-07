<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Server\TokenEndpoint\AuthenticationMethod;

use Jose\Component\Checker\ClaimCheckerManager;
use Jose\Component\Core\JWKSet;
use Jose\Component\Encryption\JWELoader;
use Jose\Component\Signature\JWSLoader;
use OAuth2Framework\Component\Server\Core\Client\Client;
use OAuth2Framework\Component\Server\Core\Client\ClientId;
use OAuth2Framework\Component\Server\Core\DataBag\DataBag;
use OAuth2Framework\Component\Server\Core\Response\OAuth2Exception;
use Psr\Http\Message\ServerRequestInterface;

final class ClientAssertionJwt implements AuthenticationMethod
{
    /**
     * @var JWSLoader
     */
    private $jwsLoader;

    /**
     * @var null|JWELoader
     */
    private $jweLoader = null;

    /**
     * @var null|JWKSet
     */
    private $keyEncryptionKeySet = null;

    /**
     * @var bool
     */
    private $encryptionRequired = false;

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
     * @param ClaimCheckerManager  $claimCheckerManager
     * @param int                  $secretLifetime
     */
    public function __construct(JWSLoader $jwsLoader, ClaimCheckerManager $claimCheckerManager, int $secretLifetime = 0)
    {
        if ($secretLifetime < 0) {
            throw new \InvalidArgumentException('The secret lifetime must be at least 0 (= unlimited).');
        }
        $this->jwsLoader = $jwsLoader;
        $this->claimCheckerManager = $claimCheckerManager;
        $this->secretLifetime = $secretLifetime;
    }

    /**
     * @param JWELoader $jweLoader
     * @param JWKSet    $keyEncryptionKeySet
     * @param bool      $encryptionRequired
     */
    public function enableEncryptedAssertions(JWELoader $jweLoader, JWKSet $keyEncryptionKeySet, bool $encryptionRequired)
    {
        $this->jweLoader = $jweLoader;
        $this->encryptionRequired = $encryptionRequired;
        $this->keyEncryptionKeySet = $keyEncryptionKeySet;
    }

    /**
     * @return string[]
     */
    public function getSupportedSignatureAlgorithms(): array
    {
        return $this->jwsLoader->getSignatureAlgorithmManager()->list();
    }

    /**
     * @return string[]
     */
    public function getSupportedContentEncryptionAlgorithms(): array
    {
        return null === $this->jweLoader ? [] : $this->jweLoader->getContentEncryptionAlgorithmManager()->list();
    }

    /**
     * @return string[]
     */
    public function getSupportedKeyEncryptionAlgorithms(): array
    {
        return null === $this->jweLoader ? [] : $this->jweLoader->getKeyEncryptionAlgorithmManager()->list();
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
    public function findClientIdAndCredentials(ServerRequestInterface $request, &$clientCredentials = null): ? ClientId
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
            if (!array_key_exists('client_assertion', $parameters)) {
                throw new \InvalidArgumentException('Parameter "client_assertion" is missing.');
            }
            $client_assertion = $parameters['client_assertion'];
            $client_assertion = $this->tryToDecryptClientAssertion($client_assertion);
            $jws = $this->jwsSerializer->unserialize($client_assertion);
            if (1 !== $jws->countSignatures()) {
                throw new \InvalidArgumentException('The assertion must have only one signature.');
            }
            $claims = json_decode($jws->getPayload(), true);
            $claims = $this->claimCheckerManager->check($claims);

            $diff = array_diff(['iss', 'sub', 'aud', 'jti', 'exp'], array_keys($claims));
            if (!empty($diff)) {
                throw new \InvalidArgumentException(sprintf('The following claim(s) is/are mandatory: "%s".', implode(', ', array_values($diff))));
            }
            if ($claims['sub'] !== $claims['iss']) {
                throw new \InvalidArgumentException('The claims "sub" and "iss" must contain the client public ID.');
            }
        } catch (\Exception $e) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_REQUEST, $e->getMessage(), [], $e);
        }

        $clientCredentials = $jws;

        return ClientId::create($claims['sub']);
    }

    /**
     * @param string $assertion
     *
     * @return string
     *
     * @throws OAuth2Exception
     */
    private function tryToDecryptClientAssertion(string $assertion): string
    {
        if (null === $this->jweLoader) {
            return $assertion;
        }

        try {
            $jwe = $this->jweLoader->loadAndDecryptWithKeySet($assertion, $this->keyEncryptionKeySet, $recipient);
            if (1 !== $jwe->countRecipients()) {
                throw new \InvalidArgumentException('The client assertion must have only one recipient.');
            }

            return $jwe->getPayload();
        } catch (\Exception $e) {
            if (true === $this->encryptionRequired) {
                throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_REQUEST, $e->getMessage(), [], $e);
            }

            return $assertion;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isClientAuthenticated(Client $client, $clientCredentials, ServerRequestInterface $request): bool
    {
        try {
            //Get the JWKSet depending on the client configuration and parameters
            $jwkSet = $client->getPublicKeySet();
            Assertion::isInstanceOf($jwkSet, JWKSet::class);
            $this->jwsVerifier->verifyWithKeySet($clientCredentials, $jwkSet);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedMethods(): array
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
            if (!($commandParameters->has('jwks') xor $commandParameters->has('jwks_uri'))) {
                throw new \InvalidArgumentException('The parameter "jwks" or "jwks_uri" must be set.');
            }
            if ($commandParameters->has('jwks')) {
                $jwks = JWKSet::createFromKeyData($commandParameters->get('jwks'));
                if (!$jwks instanceof JWKSet) {
                    throw new \InvalidArgumentException('The parameter "jwks" must be a valid JWKSet object.');
                }
                $validatedParameters = $validatedParameters->with('jwks', $commandParameters->get('jwks'));
            }/* else { FIXME
                $jwks = JWKFactory::createFromJKU($commandParameters->get('jwks_uri'));
                Assertion::isInstanceOf($jwks, JWKSet::class, 'The parameter "jwks_uri" must be a valid uri that provide a valid JWKSet.');
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
    private function createClientSecret(): string
    {
        return bin2hex(random_bytes(128));
    }
}
