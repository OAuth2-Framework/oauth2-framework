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

use Base64Url\Base64Url;
use Jose\Component\Checker\ClaimCheckerManager;
use Jose\Component\Checker\HeaderCheckerManager;
use Jose\Component\Core\Converter\JsonConverter;
use Jose\Component\Core\JWK;
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
use OAuth2Framework\Component\TrustedIssuer\TrustedIssuerRepository;
use Psr\Http\Message\ServerRequestInterface;

class ClientAssertionJwt implements AuthenticationMethod
{
    private const CLIENT_ASSERTION_TYPE = 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer';

    /**
     * @var JWSVerifier
     */
    private $jwsVerifier;

    /**
     * @var null|TrustedIssuerRepository
     */
    private $trustedIssuerRepository = null;

    /**
     * @var null|JKUFactory
     */
    private $jkuFactory = null;

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
     * @var HeaderCheckerManager
     */
    private $headerCheckerManager;

    /**
     * @var ClaimCheckerManager
     */
    private $claimCheckerManager;

    /**
     * @var JsonConverter
     */
    private $jsonConverter;

    /**
     * ClientAssertionJwt constructor.
     *
     * @param JsonConverter        $jsonConverter
     * @param JWSVerifier          $jwsVerifier
     * @param HeaderCheckerManager $headerCheckerManager
     * @param ClaimCheckerManager  $claimCheckerManager
     * @param int                  $secretLifetime
     */
    public function __construct(JsonConverter $jsonConverter, JWSVerifier $jwsVerifier, HeaderCheckerManager $headerCheckerManager, ClaimCheckerManager $claimCheckerManager, int $secretLifetime = 0)
    {
        if ($secretLifetime < 0) {
            throw new \InvalidArgumentException('The secret lifetime must be at least 0 (= unlimited).');
        }
        $this->jsonConverter = $jsonConverter;
        $this->jwsVerifier = $jwsVerifier;
        $this->headerCheckerManager = $headerCheckerManager;
        $this->claimCheckerManager = $claimCheckerManager;
        $this->secretLifetime = $secretLifetime;
    }

    /**
     * @param TrustedIssuerRepository $trustedIssuerRepository
     */
    public function enableTrustedIssuerSupport(TrustedIssuerRepository $trustedIssuerRepository)
    {
        $this->trustedIssuerRepository = $trustedIssuerRepository;
    }

    /**
     * @param JKUFactory $jkuFactory
     */
    public function enableJkuSupport(JKUFactory $jkuFactory)
    {
        $this->jkuFactory = $jkuFactory;
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
        return null === $this->jweLoader ? [] : $this->jweLoader->getJweDecrypter()->getContentEncryptionAlgorithmManager()->list();
    }

    /**
     * @return string[]
     */
    public function getSupportedKeyEncryptionAlgorithms(): array
    {
        return null === $this->jweLoader ? [] : $this->jweLoader->getJweDecrypter()->getKeyEncryptionAlgorithmManager()->list();
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

        if (self::CLIENT_ASSERTION_TYPE !== $clientAssertionType) {
            return null;
        }
        if (!array_key_exists('client_assertion', $parameters)) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_REQUEST, 'Parameter "client_assertion" is missing.');
        }

        try {
            $client_assertion = $parameters['client_assertion'];
            $client_assertion = $this->tryToDecryptClientAssertion($client_assertion);
            $serializer = new CompactSerializer($this->jsonConverter);
            $jws = $serializer->unserialize($client_assertion);
            $this->headerCheckerManager->check($jws, 0);
            $claims = $this->jsonConverter->decode($jws->getPayload());
            $this->claimCheckerManager->check($claims);
        } catch (OAuth2Exception $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_REQUEST, 'Unable to load, decrypt or verify the client assertion.', $e);
        }

        // FIXME: Other claims can be considered as mandatory by the server
        $diff = array_diff(['iss', 'sub', 'aud', 'exp'], array_keys($claims));
        if (!empty($diff)) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_REQUEST, sprintf('The following claim(s) is/are mandatory: "%s".', implode(', ', array_values($diff))));
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
                throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_REQUEST, 'The encryption of the assertion is mandatory but the decryption of the assertion failed.', $e);
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
            if (!$clientCredentials instanceof JWS) {
                return false;
            }

            $claims = $this->jsonConverter->decode($clientCredentials->getPayload());
            $jwkset = $this->retrieveIssuerKeySet($client, $clientCredentials, $claims);

            return $this->jwsVerifier->verifyWithKeySet($clientCredentials, $jwkset, 0);
        } catch (\Exception $e) {
            return false;
        }
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
        switch ($commandParameters->get('token_endpoint_auth_method')) {
            case 'client_secret_jwt':
                return $this->processClientSecretJwtConfiguration($commandParameters, $validatedParameters);
            case 'private_key_jwt':
                return $this->processPrivateKeyJwtConfiguration($commandParameters, $validatedParameters);
            default:
                return $validatedParameters;
        }
    }

    /**
     * @param DataBag $commandParameters
     * @param DataBag $validatedParameters
     *
     * @return DataBag
     */
    private function processClientSecretJwtConfiguration(DataBag $commandParameters, DataBag $validatedParameters): DataBag
    {
        $validatedParameters = $validatedParameters->with('token_endpoint_auth_method', $commandParameters->get('token_endpoint_auth_method'));
        $validatedParameters = $validatedParameters->with('client_secret', $this->createClientSecret());
        $validatedParameters = $validatedParameters->with('client_secret_expires_at', (0 === $this->secretLifetime ? 0 : time() + $this->secretLifetime));

        return $validatedParameters;
    }

    /**
     * @param DataBag $commandParameters
     * @param DataBag $validatedParameters
     *
     * @return DataBag
     */
    private function processPrivateKeyJwtConfiguration(DataBag $commandParameters, DataBag $validatedParameters): DataBag
    {
        switch (true) {
            case $commandParameters->has('jwks') && $commandParameters->has('jwks_uri'):
            case !$commandParameters->has('jwks') && !$commandParameters->has('jwks_uri') && null === $this->trustedIssuerRepository:
                throw new \InvalidArgumentException('Either the parameter "jwks" or "jwks_uri" must be set.');
            case !$commandParameters->has('jwks') && !$commandParameters->has('jwks_uri') && null !== $this->trustedIssuerRepository: //Allowed when trusted issuer support is set
                $validatedParameters = $validatedParameters->with('token_endpoint_auth_method', $commandParameters->get('token_endpoint_auth_method'));

                break;
            case $commandParameters->has('jwks'):
                try {
                    JWKSet::createFromJson($commandParameters->get('jwks'));
                } catch (\Throwable $e) {
                    throw new \InvalidArgumentException('The parameter "jwks" must be a valid JWKSet object.', 0, $e);
                }
                $validatedParameters = $validatedParameters->with('token_endpoint_auth_method', $commandParameters->get('token_endpoint_auth_method'));
                $validatedParameters = $validatedParameters->with('jwks', $commandParameters->get('jwks'));

                break;
            case $commandParameters->has('jwks_uri'):
                if (null === $this->jkuFactory) {
                    throw new \InvalidArgumentException('Distant key sets cannot be used. Please use "jwks" instead of "jwks_uri".');
                }

                try {
                    $jwks = $this->jkuFactory->loadFromUrl($commandParameters->get('jwks_uri'));
                } catch (\Exception $e) {
                    throw new \InvalidArgumentException('The parameter "jwks_uri" must be a valid uri to a JWKSet.', 0, $e);
                }
                if (0 === $jwks->count()) {
                    throw new \InvalidArgumentException('The distant key set is empty.');
                }
                $validatedParameters = $validatedParameters->with('token_endpoint_auth_method', $commandParameters->get('token_endpoint_auth_method'));
                $validatedParameters = $validatedParameters->with('jwks_uri', $commandParameters->get('jwks_uri'));

                break;
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

    /**
     * @param Client $client
     * @param JWS    $jws
     * @param array  $claims
     *
     * @return JWKSet
     */
    private function retrieveIssuerKeySet(Client $client, JWS $jws, array $claims): JWKSet
    {
        if ($claims['sub'] === $claims['iss']) { // The client is the issuer
            return $this->getClientKeySet($client);
        }

        if (null === $this->trustedIssuerRepository || null === $trustedIssuer = $this->trustedIssuerRepository->find($claims['iss'])) {
            throw new \InvalidArgumentException('Unable to retrieve the key set of the issuer.');
        }

        if (!in_array(self::CLIENT_ASSERTION_TYPE, $trustedIssuer->getAllowedAssertionTypes())) {
            throw new \InvalidArgumentException(sprintf('The assertion type "%s" is not allowed for that issuer.', self::CLIENT_ASSERTION_TYPE));
        }

        $signatureAlgorithm = $jws->getSignature(0)->getProtectedHeaderParameter('alg');
        if (!in_array($signatureAlgorithm, $trustedIssuer->getAllowedSignatureAlgorithms())) {
            throw new \InvalidArgumentException(sprintf('The signature algorithm "%s" is not allowed for that issuer.', $signatureAlgorithm));
        }

        return $trustedIssuer->getJWKSet();
    }

    /**
     * @param Client $client
     *
     * @return JWKSet
     */
    private function getClientKeySet(Client $client): JWKSet
    {
        switch (true) {
            case $client->has('jwks') && 'private_key_jwt' === $client->getTokenEndpointAuthenticationMethod():
                return JWKSet::createFromJson($client->get('jwks'));
            case $client->has('client_secret') && 'client_secret_jwt' === $client->getTokenEndpointAuthenticationMethod():
                $jwk = JWK::create([
                    'kty' => 'oct',
                    'use' => 'sig',
                    'k' => Base64Url::encode($client->get('client_secret')),
                ]);

                return JWKSet::createFromKeys([$jwk]);
            case $client->has('jwks_uri') && 'private_key_jwt' === $client->getTokenEndpointAuthenticationMethod() && null !== $this->jkuFactory:
                return $this->jkuFactory->loadFromUrl($client->get('jwks_uri'));
            default:
                throw new \InvalidArgumentException('The client has no key or key set.');
        }
    }
}
