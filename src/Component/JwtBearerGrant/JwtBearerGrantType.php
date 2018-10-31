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

namespace OAuth2Framework\Component\JwtBearerGrant;

use Base64Url\Base64Url;
use Jose\Component\Checker\ClaimCheckerManager;
use Jose\Component\Checker\HeaderCheckerManager;
use Jose\Component\Core\Converter\JsonConverter;
use Jose\Component\Core\JWK;
use Jose\Component\Core\JWKSet;
use Jose\Component\Encryption\JWEDecrypter;
use Jose\Component\Encryption\Serializer\CompactSerializer as JweCompactSerializer;
use Jose\Component\KeyManagement\JKUFactory;
use Jose\Component\Signature\JWS;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer as JwsCompactSerializer;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\Client\ClientRepository;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\TrustedIssuer\TrustedIssuerRepository;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountRepository;
use OAuth2Framework\Component\Core\Util\RequestBodyParser;
use OAuth2Framework\Component\TokenEndpoint\GrantType;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeData;
use Psr\Http\Message\ServerRequestInterface;

class JwtBearerGrantType implements GrantType
{
    /**
     * @var JsonConverter
     */
    private $jsonConverter;

    /**
     * @var JWSVerifier
     */
    private $jwsVerifier;

    /**
     * @var JWEDecrypter|null
     */
    private $jweDecrypter = null;

    /**
     * @var HeaderCheckerManager
     */
    private $headerCheckerManager;

    /**
     * @var ClaimCheckerManager
     */
    private $claimCheckerManager;

    /**
     * @var ClientRepository
     */
    private $clientRepository;

    /**
     * @var UserAccountRepository|null
     */
    private $userAccountRepository;

    /**
     * @var bool
     */
    private $encryptionRequired = false;

    /**
     * @var JWKSet|null
     */
    private $keyEncryptionKeySet = null;

    /**
     * @var null|TrustedIssuerRepository
     */
    private $trustedIssuerRepository = null;

    /**
     * @var null|JKUFactory
     */
    private $jkuFactory = null;

    /**
     * JWTBearerGrantType constructor.
     */
    public function __construct(JsonConverter $jsonConverter, JWSVerifier $jwsVerifier, HeaderCheckerManager $headerCheckerManager, ClaimCheckerManager $claimCheckerManager, ClientRepository $clientRepository, ?UserAccountRepository $userAccountRepository)
    {
        $this->jsonConverter = $jsonConverter;
        $this->jwsVerifier = $jwsVerifier;
        $this->headerCheckerManager = $headerCheckerManager;
        $this->claimCheckerManager = $claimCheckerManager;
        $this->clientRepository = $clientRepository;
        $this->userAccountRepository = $userAccountRepository;
    }

    public function associatedResponseTypes(): array
    {
        return [];
    }

    public function enableEncryptedAssertions(JWEDecrypter $jweDecrypter, JWKSet $keyEncryptionKeySet, bool $encryptionRequired)
    {
        $this->jweDecrypter = $jweDecrypter;
        $this->encryptionRequired = $encryptionRequired;
        $this->keyEncryptionKeySet = $keyEncryptionKeySet;
    }

    public function enableTrustedIssuerSupport(TrustedIssuerRepository $trustedIssuerRepository)
    {
        $this->trustedIssuerRepository = $trustedIssuerRepository;
    }

    public function enableJkuSupport(JKUFactory $jkuFactory)
    {
        $this->jkuFactory = $jkuFactory;
    }

    public function name(): string
    {
        return 'urn:ietf:params:oauth:grant-type:jwt-bearer';
    }

    public function checkRequest(ServerRequestInterface $request): void
    {
        $parameters = RequestBodyParser::parseFormUrlEncoded($request);
        $requiredParameters = ['assertion'];

        $diff = \array_diff($requiredParameters, \array_keys($parameters));
        if (!empty($diff)) {
            throw new OAuth2Error(400, OAuth2Error::ERROR_INVALID_REQUEST, \Safe\sprintf('Missing grant type parameter(s): %s.', \implode(', ', $diff)));
        }
    }

    public function prepareResponse(ServerRequestInterface $request, GrantTypeData $grantTypeData): void
    {
        $parameters = RequestBodyParser::parseFormUrlEncoded($request);
        $assertion = $parameters['assertion'];
        $assertion = $this->tryToDecryptTheAssertion($assertion);

        try {
            $jwsSerializer = new JwsCompactSerializer($this->jsonConverter);
            $jws = $jwsSerializer->unserialize($assertion);
            if (1 !== $jws->countSignatures()) {
                throw new \InvalidArgumentException('The assertion must have only one signature.');
            }
            $claims = \Safe\json_decode($jws->getPayload(), true);
            $this->claimCheckerManager->check($claims);
            $diff = \array_diff(['iss', 'sub', 'aud', 'exp'], \array_keys($claims));
            if (!empty($diff)) {
                throw new \InvalidArgumentException(\Safe\sprintf('The following claim(s) is/are mandatory: "%s".', \implode(', ', \array_values($diff))));
            }
            $this->checkJWTSignature($grantTypeData, $jws, $claims);
        } catch (OAuth2Error $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new OAuth2Error(400, OAuth2Error::ERROR_INVALID_REQUEST, $e->getMessage(), [], $e);
        }
    }

    private function tryToDecryptTheAssertion(string $assertion): string
    {
        if (null === $this->jweDecrypter) {
            return $assertion;
        }

        try {
            $jweSerializer = new JweCompactSerializer($this->jsonConverter);
            $jwe = $jweSerializer->unserialize($assertion);
            if (1 !== $jwe->countRecipients()) {
                throw new \InvalidArgumentException('The assertion must have only one recipient.');
            }
            if (true === $this->jweDecrypter->decryptUsingKeySet($jwe, $this->keyEncryptionKeySet, 0)) {
                return $jwe->getPayload();
            }

            throw new \InvalidArgumentException('Unable to decrypt the assertion.');
        } catch (\Exception $e) {
            if (true === $this->encryptionRequired) {
                throw new OAuth2Error(400, OAuth2Error::ERROR_INVALID_REQUEST, $e->getMessage(), [], $e);
            }

            return $assertion;
        }
    }

    public function grant(ServerRequestInterface $request, GrantTypeData $grantTypeData): void
    {
    }

    private function checkJWTSignature(GrantTypeData $grantTypeData, JWS $jws, array $claims): void
    {
        $iss = $claims['iss'];
        $sub = $claims['sub'];

        if ($iss === $sub) { // The issuer is the resource owner
            $client = $this->clientRepository->find(new ClientId($iss));

            if (null === $client || true === $client->isDeleted()) {
                throw new OAuth2Error(400, OAuth2Error::ERROR_INVALID_GRANT, 'Unable to find the issuer of the assertion.');
            }
            if (null === $grantTypeData->getClient()) {
                $grantTypeData->setClient($client);
            } elseif ($grantTypeData->getClient()->getPublicId()->getValue() !== $client->getPublicId()->getValue()) {
                throw new OAuth2Error(401, OAuth2Error::ERROR_INVALID_CLIENT, 'Client authentication failed.');
            }
            $grantTypeData->setResourceOwnerId($client->getPublicId());
            $allowedSignatureAlgorithms = $this->jwsVerifier->getSignatureAlgorithmManager()->list();
            $signatureKeys = $this->getClientKeySet($client);
        } elseif (null !== $this->trustedIssuerRepository) { // Trusted issuer support
            $issuer = $this->trustedIssuerRepository->find($iss);
            if (null === $issuer) {
                throw new \InvalidArgumentException('Unable to find the issuer of the assertion.');
            }
            $allowedSignatureAlgorithms = $issuer->getAllowedSignatureAlgorithms();
            $signatureKeys = $issuer->getJWKSet();
            $resourceOwnerId = $this->findResourceOwner($sub);
            if (null === $resourceOwnerId) {
                throw new \InvalidArgumentException(\Safe\sprintf('Unknown resource owner with ID "%s"', $sub));
            }
            $grantTypeData->setResourceOwnerId($resourceOwnerId);
        } else {
            throw new \InvalidArgumentException('Unable to find the issuer of the assertion.');
        }

        if (!$jws->getSignature(0)->hasProtectedHeaderParameter('alg') || !\in_array($jws->getSignature(0)->getProtectedHeaderParameter('alg'), $allowedSignatureAlgorithms, true)) {
            throw new \InvalidArgumentException(\Safe\sprintf('The signature algorithm "%s" is not allowed.', $jws->getSignature(0)->getProtectedHeaderParameter('alg')));
        }

        $this->jwsVerifier->verifyWithKeySet($jws, $signatureKeys, 0);
        $grantTypeData->getMetadata()->set('jwt', $jws);
        $grantTypeData->getMetadata()->set('claims', $claims);
    }

    private function findResourceOwner(string $subject): ?ResourceOwnerId
    {
        $userAccount = $this->userAccountRepository ? $this->userAccountRepository->find(new UserAccountId($subject)) : null;
        if (null !== $userAccount) {
            return $userAccount->getUserAccountId();
        }
        $client = $this->clientRepository->find(new ClientId($subject));
        if (null !== $client) {
            return $client->getPublicId();
        }

        return null;
    }

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
