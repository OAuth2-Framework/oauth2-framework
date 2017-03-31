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

namespace OAuth2Framework\Component\Server\GrantType;

use Assert\Assertion;
use Jose\JWTLoaderInterface;
use Jose\Object\JWKSetInterface;
use Jose\Object\JWSInterface;
use OAuth2Framework\Component\Server\Endpoint\Token\GrantTypeData;
use OAuth2Framework\Component\Server\Model\Client\ClientId;
use OAuth2Framework\Component\Server\Model\Client\ClientRepositoryInterface;
use OAuth2Framework\Component\Server\Model\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Server\Model\TrustedIssuer\TrustedIssuerInterface;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountRepositoryInterface;
use OAuth2Framework\Component\Server\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\Response\OAuth2ResponseFactoryManager;
use Psr\Http\Message\ServerRequestInterface;

final class JWTBearerGrantType implements GrantTypeInterface
{
    /**
     * @var JWTLoaderInterface
     */
    private $jwtLoader;

    /**
     * @var ClientRepositoryInterface
     */
    private $clientRepository;

    /**
     * @var UserAccountRepositoryInterface
     */
    private $userAccountRepository;

    /**
     * @var bool
     */
    private $encryptionRequired = false;

    /**
     * @var JWKSetInterface|null
     */
    private $keyEncryptionKeySet = null;

    /**
     * @var TrustedIssuerInterface[]
     */
    private $trustedIssuers = [];

    /**
     * JWTBearerGrantType constructor.
     *
     * @param JWTLoaderInterface             $jwsLoader
     * @param ClientRepositoryInterface      $clientRepository
     * @param UserAccountRepositoryInterface $userAccountRepository
     */
    public function __construct(JWTLoaderInterface $jwsLoader, ClientRepositoryInterface $clientRepository, UserAccountRepositoryInterface $userAccountRepository)
    {
        $this->jwtLoader = $jwsLoader;
        $this->clientRepository = $clientRepository;
        $this->userAccountRepository = $userAccountRepository;
    }

    /**
     * @param TrustedIssuerInterface $trustedIssuer
     *
     * @return JWTBearerGrantType
     */
    public function addTrustedIssuer(TrustedIssuerInterface $trustedIssuer): JWTBearerGrantType
    {
        $name = $trustedIssuer->name();
        $this->trustedIssuers[$name] = $trustedIssuer;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAssociatedResponseTypes(): array
    {
        return [];
    }

    /**
     * @param bool            $encryptionRequired
     * @param JWKSetInterface $keyEncryptionKeySet
     */
    public function enableEncryptedAssertions(bool $encryptionRequired, JWKSetInterface $keyEncryptionKeySet)
    {
        Assertion::boolean($encryptionRequired);

        $this->encryptionRequired = $encryptionRequired;
        $this->keyEncryptionKeySet = $keyEncryptionKeySet;
    }

    /**
     * {@inheritdoc}
     */
    public function getGrantType(): string
    {
        return 'urn:ietf:params:oauth:grant-type:jwt-bearer';
    }

    public function checkTokenRequest(ServerRequestInterface $request)
    {
        $parameters = $request->getParsedBody() ?? [];
        $requiredParameters = ['assertion'];

        foreach ($requiredParameters as $requiredParameter) {
            if (!array_key_exists($requiredParameter, $parameters)) {
                throw new OAuth2Exception(400, ['error' => OAuth2ResponseFactoryManager::ERROR_INVALID_REQUEST, 'error_description' => sprintf('The parameter \'%s\' is missing.', $requiredParameter)]);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function prepareTokenResponse(ServerRequestInterface $request, GrantTypeData $grantTypeResponse): GrantTypeData
    {
        $parameters = $request->getParsedBody() ?? [];
        $assertion = $parameters['assertion'];
        try {
            $jws = $this->jwtLoader->load($assertion, $this->keyEncryptionKeySet, $this->encryptionRequired);
            Assertion::isInstanceOf($jws, JWSInterface::class, 'Assertion does not contain signed claims.');
            Assertion::eq(1, $jws->countSignatures(), 'Assertion must have only one signature.');
            Assertion::true($jws->hasClaim('iss'), 'Assertion does not contain \'iss\' claims.');
            Assertion::true($jws->hasClaim('sub'), 'Assertion does not contain \'sub\' claims.');
            $grantTypeResponse = $this->checkJWTSignature($grantTypeResponse, $jws);
        } catch (OAuth2Exception $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new OAuth2Exception(400, ['error' => OAuth2ResponseFactoryManager::ERROR_INVALID_REQUEST, 'error_description' => $e->getMessage()]);
        }
        $grantTypeResponse = $grantTypeResponse->withoutRefreshToken();

        return $grantTypeResponse;
    }

    /**
     * {@inheritdoc}
     */
    public function grant(ServerRequestInterface $request, GrantTypeData $grantTypeResponse): GrantTypeData
    {
        //Nothing to do
        return $grantTypeResponse;
    }

    /**
     * @param GrantTypeData $grantTypeResponse
     * @param JWSInterface  $jws
     *
     * @throws OAuth2Exception
     *
     * @return GrantTypeData
     */
    private function checkJWTSignature(GrantTypeData $grantTypeResponse, JWSInterface $jws): GrantTypeData
    {
        $iss = $jws->getClaim('iss');
        $sub = $jws->getClaim('sub');
        if (array_key_exists($iss, $this->trustedIssuers)) {
            $issuer = $this->trustedIssuers[$iss];
            $allowedSignatureAlgorithms = $issuer->getAllowedSignatureAlgorithms();
            $signatureKeys = $issuer->getSignatureKeys();
            $resourceOwnerId = $this->findResourceOwner($sub);
            $grantTypeResponse = $grantTypeResponse->withResourceOwnerId($resourceOwnerId);
        } else {
            $client = $this->clientRepository->find(ClientId::create($iss));
            if (null === $client || true === $client->isDeleted()) {
                throw new  OAuth2Exception(400, ['error' => OAuth2ResponseFactoryManager::ERROR_INVALID_GRANT, 'error_description' => 'Unable to find the assertion issuer.']);
            }
            if (null === $grantTypeResponse->getClient()) {
                $grantTypeResponse = $grantTypeResponse->withClient($client);
            } elseif ($grantTypeResponse->getClient()->getPublicId()->getValue() !== $client->getPublicId()->getValue()) {
                throw new  OAuth2Exception(401, ['error' => OAuth2ResponseFactoryManager::ERROR_INVALID_CLIENT, 'error_description' => 'Client authentication failed.']);
            }
            Assertion::eq($sub, $iss, 'When the client is the assertion issuer then the subject must be the client.');
            $grantTypeResponse = $grantTypeResponse->withResourceOwnerId($client->getPublicId());
            $allowedSignatureAlgorithms = $this->jwtLoader->getSupportedSignatureAlgorithms();
            $signatureKeys = $client->getPublicKeySet();
        }

        Assertion::true($jws->getSignature(0)->hasProtectedHeader('alg'), 'Invalid assertion');
        $alg = $jws->getSignature(0)->getProtectedHeader('alg');
        Assertion::inArray($alg, $allowedSignatureAlgorithms, sprintf('The signature algorithm \'%s\' is not allowed.', $alg));
        $this->jwtLoader->verify($jws, $signatureKeys);
        $grantTypeResponse = $grantTypeResponse->withMetadata('jwt', $jws);

        return $grantTypeResponse;
    }

    /**
     * @param string $subject
     *
     * @return ResourceOwnerId|null
     */
    private function findResourceOwner(string $subject): ?ResourceOwnerId
    {
        $userAccount = $this->userAccountRepository->findUserAccount(UserAccountId::create($subject));
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
