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

namespace OAuth2Framework\Component\ClientAuthentication;

use Jose\Component\Checker\ClaimCheckerManager;
use Jose\Component\Core\Converter\StandardConverter;
use Jose\Component\Core\JWKSet;
use Jose\Component\Encryption\JWELoader;
use Jose\Component\KeyManagement\JKUFactory;
use Jose\Component\Signature\JWS;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\Exception\OAuth2Exception;
use Psr\Http\Message\ServerRequestInterface;

class ClientAssertionJwt implements AuthenticationMethod
{
    /**
     * @var TrustedIssuerManager
     */
    private $trustedIssuerManager;

    /**
     * @var JWSVerifier
     */
    private $jwsVerifier;

    /**
     * @var JKUFactory
     */
    private $jkuFactory;

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
     * @param TrustedIssuerManager $trustedIssuerManager
     * @param JKUFactory           $jkuFactory
     * @param JWSVerifier          $jwsVerifier
     * @param ClaimCheckerManager  $claimCheckerManager
     * @param int                  $secretLifetime
     */
    public function __construct(TrustedIssuerManager $trustedIssuerManager, JKUFactory $jkuFactory, JWSVerifier $jwsVerifier, ClaimCheckerManager $claimCheckerManager, int $secretLifetime = 0)
    {
        if ($secretLifetime < 0) {
            throw new \InvalidArgumentException('The secret lifetime must be at least 0 (= unlimited).');
        }
        $this->trustedIssuerManager = $trustedIssuerManager;
        $this->jkuFactory = $jkuFactory;
        $this->jwsVerifier = $jwsVerifier;
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
        return $this->jwsVerifier->getSignatureAlgorithmManager()->list();
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

        if ('urn:ietf:params:oauth:client-assertion-type:jwt-bearer' !== $clientAssertionType) {
            return null;
        }

        try {
            if (!array_key_exists('client_assertion', $parameters)) {
                throw new \InvalidArgumentException('Parameter "client_assertion" is missing.');
            }
            $client_assertion = $parameters['client_assertion'];
            $client_assertion = $this->tryToDecryptClientAssertion($client_assertion);
            $serializer = new CompactSerializer(new StandardConverter());
            $jws = $serializer->unserialize($client_assertion);
            if (1 !== $jws->countSignatures()) {
                throw new \InvalidArgumentException('The assertion must have only one signature.');
            }
            $claims = json_decode($jws->getPayload(), true);

            // Other claims can be considered as mandatory
            $diff = array_diff(['iss', 'sub', 'aud', 'exp'], array_keys($claims));
            if (!empty($diff)) {
                throw new \InvalidArgumentException(sprintf('The following claim(s) is/are mandatory: "%s".', implode(', ', array_values($diff))));
            }

            $clientCredentials = $jws;

            return ClientId::create($claims['sub']);
        } catch (\Exception $e) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_REQUEST, $e->getMessage(), $e);
        }
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
                throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_REQUEST, $e->getMessage(), $e);
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
            /** @var JWS $jws */
            $jws = $clientCredentials;

            //Retrieve the JWKSet from the client configuration or the trusted issuer (see iss claim)
            $this->jwsVerifier->verifyWithKeySet($jws);
            // The claim checker manager should return only checked claims
            $claims = $this->claimCheckerManager->check($claims);

            //Trusted issuers should be supported
            if ($claims['sub'] !== $claims['iss']) {
                throw new \InvalidArgumentException('The claims "sub" and "iss" must contain the client public ID.');
            }
            //Get the JWKSet depending on the client configuration and parameters
            $jwkSet = $client->getPublicKeySet();
            //Assertion::isInstanceOf($jwkSet, JWKSet::class);
            $this->jwsVerifier->verifyWithKeySet($clientCredentials, $jwkSet, $signature);
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
            } else {
                $jwks = $this->jkuFactory->loadFromUrl($commandParameters->get('jwks_uri'));
                if (empty($jwks)) {
                    throw new \InvalidArgumentException('The parameter "jwks_uri" must be a valid uri to a JWK Set and at least one key.');
                }
                $validatedParameters = $validatedParameters->with('jwks_uri', $commandParameters->get('jwks_uri'));
            }
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
