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

namespace OAuth2Framework\Component\Server\JwtBearerGrant;

use Jose\Component\Checker\ClaimCheckerManager;
use Jose\Component\Core\JWKSet;
use Jose\Component\Encryption\JWEDecrypter;
use Jose\Component\Encryption\Serializer\CompactSerializer as JweCompactSerializer;
use Jose\Component\Signature\JWS;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer as JwsCompactSerializer;
use OAuth2Framework\Component\Server\TokenEndpoint\GrantTypeData;
use OAuth2Framework\Component\Server\Core\Client\ClientId;
use OAuth2Framework\Component\Server\Core\Client\ClientRepository;
use OAuth2Framework\Component\Server\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Server\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\Server\Core\UserAccount\UserAccountRepository;
use OAuth2Framework\Component\Server\Core\Exception\OAuth2Exception;
use OAuth2Framework\Component\Server\TokenEndpoint\GrantType;
use Psr\Http\Message\ServerRequestInterface;

final class JwtBearerGrantType implements GrantType
{
    /**
     * @var JwsCompactSerializer
     */
    private $jwsSerializer;

    /**
     * @var JWSVerifier
     */
    private $jwsVerifier;

    /**
     * @var JweCompactSerializer|null
     */
    private $jweSerializer;

    /**
     * @var JWEDecrypter|null
     */
    private $jweDecrypter;

    /**
     * @var ClaimCheckerManager
     */
    private $claimCheckerManager;

    /**
     * @var ClientRepository
     */
    private $clientRepository;

    /**
     * @var UserAccountRepository
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
     * @var TrustedIssuerManager
     */
    private $trustedIssuerManager;

    /**
     * JWTBearerGrantType constructor.
     *
     * @param TrustedIssuerManager  $trustedIssuerManager
     * @param JwsCompactSerializer  $jwsSerializer
     * @param JWSVerifier           $jwsVerifier
     * @param ClaimCheckerManager   $claimCheckerManager
     * @param ClientRepository      $clientRepository
     * @param UserAccountRepository $userAccountRepository
     */
    public function __construct(TrustedIssuerManager $trustedIssuerManager, JwsCompactSerializer $jwsSerializer, JWSVerifier $jwsVerifier, ClaimCheckerManager $claimCheckerManager, ClientRepository $clientRepository, UserAccountRepository $userAccountRepository)
    {
        $this->jwsSerializer = $jwsSerializer;
        $this->trustedIssuerManager = $trustedIssuerManager;
        $this->jwsVerifier = $jwsVerifier;
        $this->claimCheckerManager = $claimCheckerManager;
        $this->clientRepository = $clientRepository;
        $this->userAccountRepository = $userAccountRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function associatedResponseTypes(): array
    {
        return [];
    }

    /**
     * @param JweCompactSerializer $jweSerializer
     * @param JWEDecrypter         $jweDecrypter
     * @param JWKSet               $keyEncryptionKeySet
     * @param bool                 $encryptionRequired
     */
    public function enableEncryptedAssertions(JweCompactSerializer $jweSerializer, JWEDecrypter $jweDecrypter, JWKSet $keyEncryptionKeySet, bool $encryptionRequired)
    {
        $this->jweSerializer = $jweSerializer;
        $this->jweDecrypter = $jweDecrypter;
        $this->encryptionRequired = $encryptionRequired;
        $this->keyEncryptionKeySet = $keyEncryptionKeySet;
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'urn:ietf:params:oauth:grant-type:jwt-bearer';
    }

    /**
     * {@inheritdoc}
     */
    public function checkRequest(ServerRequestInterface $request)
    {
        $parameters = $request->getParsedBody() ?? [];
        $requiredParameters = ['assertion'];

        $diff = array_diff($requiredParameters, array_keys($parameters));
        if (!empty($diff)) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_REQUEST, sprintf('Missing grant type parameter(s): %s.', implode(', ', $diff)));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function prepareResponse(ServerRequestInterface $request, GrantTypeData $grantTypeData): GrantTypeData
    {
        $parameters = $request->getParsedBody() ?? [];
        $assertion = $parameters['assertion'];
        $assertion = $this->tryToDecryptTheAssertion($assertion);

        try {
            $jws = $this->jwsSerializer->unserialize($assertion);
            if (1 !== $jws->countSignatures()) {
                throw new \InvalidArgumentException('The assertion must have only one signature.');
            }
            $claims = json_decode($jws->getPayload(), true);
            $claims = $this->claimCheckerManager->check($claims);
            foreach (['iss', 'sub'] as $claim) {
                if (!array_key_exists($claim, $claims)) {
                    throw new \InvalidArgumentException(sprintf('The assertion must contain the claim member "%s".', $claim));
                }
            }
            $grantTypeData = $this->checkJWTSignature($grantTypeData, $jws, $claims);
        } catch (OAuth2Exception $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_REQUEST, $e->getMessage(), $e);
        }

        return $grantTypeData;
    }

    /**
     * @param string $assertion
     *
     * @return string
     *
     * @throws OAuth2Exception
     */
    private function tryToDecryptTheAssertion(string $assertion): string
    {
        if (null === $this->jweDecrypter) {
            return $assertion;
        }

        try {
            $jwe = $this->jweSerializer->unserialize($assertion);
            if (1 !== $jwe->countRecipients()) {
                throw new \InvalidArgumentException('The assertion must have only one recipient.');
            }
            if (true === $this->jweDecrypter->decryptUsingKeySet($jwe, $this->keyEncryptionKeySet, 0)) {
                return $jwe->getPayload();
            }

            throw new \InvalidArgumentException('Unable to decrypt the assertion.');
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
    public function grant(ServerRequestInterface $request, GrantTypeData $grantTypeData): GrantTypeData
    {
        //Nothing to do
        return $grantTypeData;
    }

    /**
     * @param GrantTypeData $grantTypeData
     * @param JWS           $jws
     * @param array         $claims
     *
     * @throws OAuth2Exception
     *
     * @return GrantTypeData
     */
    private function checkJWTSignature(GrantTypeData $grantTypeData, JWS $jws, array $claims): GrantTypeData
    {
        $iss = $claims['iss'];
        $sub = $claims['sub'];

        if ($iss === $sub) { // The issuer is the resource owner
            $client = $this->clientRepository->find(ClientId::create($iss));
            if (null === $client || true === $client->isDeleted()) {
                throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_GRANT, 'Unable to find the issuer of the assertion.');
            }
            if (null === $grantTypeData->getClient()) {
                $grantTypeData = $grantTypeData->withClient($client);
            } elseif ($grantTypeData->getClient()->getPublicId()->getValue() !== $client->getPublicId()->getValue()) {
                throw new OAuth2Exception(401, OAuth2Exception::ERROR_INVALID_CLIENT, 'Client authentication failed.');
            }
            $grantTypeData = $grantTypeData->withResourceOwnerId($client->getPublicId());
            $allowedSignatureAlgorithms = $this->jwsVerifier->getSignatureAlgorithmManager()->list();
            $signatureKeys = $client->getPublicKeySet();
        } elseif ($this->trustedIssuerManager->has($iss)) { // Trusted issuer
            $issuer = $this->trustedIssuerManager->get($iss);
            $allowedSignatureAlgorithms = $issuer->getAllowedSignatureAlgorithms();
            $signatureKeys = $issuer->getSignatureKeys();
            $resourceOwnerId = $this->findResourceOwner($sub);
            if (null === $resourceOwnerId) {
                throw new \InvalidArgumentException(sprintf('Unknown resource owner with ID "%s"', $sub));
            }
            $grantTypeData = $grantTypeData->withResourceOwnerId($resourceOwnerId);
        } else {
            throw new \InvalidArgumentException('Unable to find the issuer of the assertion.');
        }

        if (!$jws->getSignature(0)->hasProtectedHeaderParameter('alg') || !in_array($jws->getSignature(0)->getProtectedHeaderParameter('alg'), $allowedSignatureAlgorithms)) {
            throw new \InvalidArgumentException(sprintf('The signature algorithm "%s" is not allowed.', $jws->getSignature(0)->getProtectedHeaderParameter('alg')));
        }

        $this->jwsVerifier->verifyWithKeySet($jws, $signatureKeys, 0);
        $grantTypeData = $grantTypeData->withMetadata('jwt', $jws);
        $grantTypeData = $grantTypeData->withMetadata('claims', $claims);

        return $grantTypeData;
    }

    /**
     * @param string $subject
     *
     * @return ResourceOwnerId|null
     */
    private function findResourceOwner(string $subject): ? ResourceOwnerId
    {
        $userAccount = $this->userAccountRepository->find(UserAccountId::create($subject));
        if (null !== $userAccount) {
            return $userAccount->getPublicId();
        }
        $client = $this->clientRepository->find(ClientId::create($subject));
        if (null !== $client) {
            return $client->getPublicId();
        }

        return null;
    }
}
