<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\ClientAuthentication;

use Jose\Component\Core\Util\JsonConverter;
use Assert\Assertion;
use Base64Url\Base64Url;
use Jose\Component\Checker\ClaimCheckerManager;
use Jose\Component\Checker\HeaderCheckerManager;
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
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\TrustedIssuer\TrustedIssuerRepository;
use OAuth2Framework\Component\Core\Util\RequestBodyParser;
use Psr\Http\Message\ServerRequestInterface;
use function Safe\json_decode;
use function Safe\json_encode;
use function Safe\sprintf;

class ClientAssertionJwt implements AuthenticationMethod
{
    private const CLIENT_ASSERTION_TYPE = 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer';

    private JWSVerifier $jwsVerifier;

    private ?TrustedIssuerRepository $trustedIssuerRepository;

    private ?JKUFactory $jkuFactory;

    private ?JWELoader $jweLoader;

    private ?JWKSet $keyEncryptionKeySet;

    private bool $encryptionRequired = false;

    private int $secretLifetime;

    private HeaderCheckerManager $headerCheckerManager;

    private ClaimCheckerManager $claimCheckerManager;

    public function __construct(JWSVerifier $jwsVerifier, HeaderCheckerManager $headerCheckerManager, ClaimCheckerManager $claimCheckerManager, int $secretLifetime = 0)
    {
        Assertion::greaterOrEqualThan($secretLifetime, 0, 'The secret lifetime must be at least 0 (= unlimited).');
        $this->jwsVerifier = $jwsVerifier;
        $this->headerCheckerManager = $headerCheckerManager;
        $this->claimCheckerManager = $claimCheckerManager;
        $this->secretLifetime = $secretLifetime;
    }

    public function enableTrustedIssuerSupport(TrustedIssuerRepository $trustedIssuerRepository): void
    {
        $this->trustedIssuerRepository = $trustedIssuerRepository;
    }

    public function enableJkuSupport(JKUFactory $jkuFactory): void
    {
        $this->jkuFactory = $jkuFactory;
    }

    public function enableEncryptedAssertions(JWELoader $jweLoader, JWKSet $keyEncryptionKeySet, bool $encryptionRequired): void
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

    public function getSchemesParameters(): array
    {
        return [];
    }

    /**
     * @param null|mixed $clientCredentials
     */
    public function findClientIdAndCredentials(ServerRequestInterface $request, &$clientCredentials = null): ?ClientId
    {
        $parameters = RequestBodyParser::parseFormUrlEncoded($request);
        if (!\array_key_exists('client_assertion_type', $parameters)) {
            return null;
        }
        $clientAssertionType = $parameters['client_assertion_type'];

        if (self::CLIENT_ASSERTION_TYPE !== $clientAssertionType) {
            return null;
        }
        if (!\array_key_exists('client_assertion', $parameters)) {
            throw OAuth2Error::invalidRequest('Parameter "client_assertion" is missing.');
        }

        try {
            $client_assertion = $parameters['client_assertion'];
            $client_assertion = $this->tryToDecryptClientAssertion($client_assertion);
            $serializer = new CompactSerializer();
            $jws = $serializer->unserialize($client_assertion);
            $this->headerCheckerManager->check($jws, 0);
            $payload = $jws->getPayload();
            Assertion::string($payload, 'Unable to get the JWS payload');
            $claims = JsonConverter::decode($payload);
            $this->claimCheckerManager->check($claims);
        } catch (OAuth2Error $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw OAuth2Error::invalidRequest('Unable to load, decrypt or verify the client assertion.', [], $e);
        }

        // FIXME: Other claims can be considered as mandatory by the server
        $diff = array_diff(['iss', 'sub', 'aud', 'exp'], array_keys($claims));
        if (0 !== \count($diff)) {
            throw OAuth2Error::invalidRequest(sprintf('The following claim(s) is/are mandatory: "%s".', implode(', ', array_values($diff))));
        }

        $clientCredentials = $jws;

        return new ClientId($claims['sub']);
    }

    /**
     * @param null|mixed $clientCredentials
     */
    public function isClientAuthenticated(Client $client, $clientCredentials, ServerRequestInterface $request): bool
    {
        try {
            if (!$clientCredentials instanceof JWS) {
                return false;
            }
            $payload = $clientCredentials->getPayload();
            Assertion::string($payload, 'No payload available');
            $claims = JsonConverter::decode($payload);
            $jwkset = $this->retrieveIssuerKeySet($client, $clientCredentials, $claims);

            return $this->jwsVerifier->verifyWithKeySet($clientCredentials, $jwkset, 0);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function getSupportedMethods(): array
    {
        return ['client_secret_jwt', 'private_key_jwt'];
    }

    public function checkClientConfiguration(DataBag $commandParameters, DataBag $validatedParameters): DataBag
    {
        switch ($commandParameters->get('token_endpoint_auth_method')) {
            case 'client_secret_jwt':
                return $this->checkClientSecretJwtConfiguration($commandParameters, $validatedParameters);
            case 'private_key_jwt':
                return $this->checkPrivateKeyJwtConfiguration($commandParameters, $validatedParameters);
            default:
                return $validatedParameters;
        }
    }

    private function tryToDecryptClientAssertion(string $assertion): string
    {
        if (null === $this->jweLoader) {
            return $assertion;
        }

        try {
            Assertion::notNull($this->keyEncryptionKeySet, 'The key encryption key set is not defined.');
            $jwe = $this->jweLoader->loadAndDecryptWithKeySet($assertion, $this->keyEncryptionKeySet, $recipient);
            if (1 !== $jwe->countRecipients()) {
                throw new \InvalidArgumentException('The client assertion must have only one recipient.');
            }
            $payload = $jwe->getPayload();
            Assertion::string($payload, 'Unable to get the JWE payload');

            return $payload;
        } catch (\Throwable $e) {
            if (true === $this->encryptionRequired) {
                throw OAuth2Error::invalidRequest('The encryption of the assertion is mandatory but the decryption of the assertion failed.', [], $e);
            }

            return $assertion;
        }
    }

    private function checkClientSecretJwtConfiguration(DataBag $commandParameters, DataBag $validatedParameters): DataBag
    {
        $validatedParameters->set('token_endpoint_auth_method', $commandParameters->get('token_endpoint_auth_method'));
        $validatedParameters->set('client_secret', $this->createClientSecret());
        $validatedParameters->set('client_secret_expires_at', (0 === $this->secretLifetime ? 0 : time() + $this->secretLifetime));

        return $validatedParameters;
    }

    private function checkPrivateKeyJwtConfiguration(DataBag $commandParameters, DataBag $validatedParameters): DataBag
    {
        switch (true) {
            case $commandParameters->has('jwks') && $commandParameters->has('jwks_uri'):
            case !$commandParameters->has('jwks') && !$commandParameters->has('jwks_uri') && null === $this->trustedIssuerRepository:
                throw new \InvalidArgumentException('Either the parameter "jwks" or "jwks_uri" must be set.');
            case !$commandParameters->has('jwks') && !$commandParameters->has('jwks_uri') && null !== $this->trustedIssuerRepository: //Allowed when trusted issuer support is set

                break;
            case $commandParameters->has('jwks'):
                $validatedParameters->set('jwks', $commandParameters->get('jwks'));

                break;
            case $commandParameters->has('jwks_uri'):
                $validatedParameters->set('jwks_uri', $commandParameters->get('jwks_uri'));

                break;
        }

        return $validatedParameters;
    }

    private function createClientSecret(): string
    {
        return bin2hex(random_bytes(32));
    }

    private function retrieveIssuerKeySet(Client $client, JWS $jws, array $claims): JWKSet
    {
        if ($claims['sub'] === $claims['iss']) { // The client is the issuer
            return $this->getClientKeySet($client);
        }

        if (null === $this->trustedIssuerRepository || null === $trustedIssuer = $this->trustedIssuerRepository->find($claims['iss'])) {
            throw new \InvalidArgumentException('Unable to retrieve the key set of the issuer.');
        }

        if (!\in_array(self::CLIENT_ASSERTION_TYPE, $trustedIssuer->getAllowedAssertionTypes(), true)) {
            throw new \InvalidArgumentException(sprintf('The assertion type "%s" is not allowed for that issuer.', self::CLIENT_ASSERTION_TYPE));
        }

        $signatureAlgorithm = $jws->getSignature(0)->getProtectedHeaderParameter('alg');
        if (!\in_array($signatureAlgorithm, $trustedIssuer->getAllowedSignatureAlgorithms(), true)) {
            throw new \InvalidArgumentException(sprintf('The signature algorithm "%s" is not allowed for that issuer.', $signatureAlgorithm));
        }

        return $trustedIssuer->getJWKSet();
    }

    private function getClientKeySet(Client $client): JWKSet
    {
        switch (true) {
            case $client->has('jwks') && 'private_key_jwt' === $client->getTokenEndpointAuthenticationMethod():
                $jwks = json_decode(json_encode($client->get('jwks'), JSON_FORCE_OBJECT), true);

                return JWKSet::createFromKeyData($jwks);
            case $client->has('client_secret') && 'client_secret_jwt' === $client->getTokenEndpointAuthenticationMethod():
                $jwk = new JWK([
                    'kty' => 'oct',
                    'use' => 'sig',
                    'k' => Base64Url::encode($client->get('client_secret')),
                ]);

                return new JWKSet([$jwk]);
            case $client->has('jwks_uri') && 'private_key_jwt' === $client->getTokenEndpointAuthenticationMethod() && null !== $this->jkuFactory:
                return $this->jkuFactory->loadFromUrl($client->get('jwks_uri'));
            default:
                throw new \InvalidArgumentException('The client has no key or key set.');
        }
    }
}
