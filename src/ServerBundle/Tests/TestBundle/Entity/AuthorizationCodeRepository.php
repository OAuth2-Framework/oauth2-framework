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

namespace OAuth2Framework\ServerBundle\Tests\TestBundle\Entity;

use Assert\Assertion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepositoryInterface;
use Doctrine\Common\Persistence\ManagerRegistry;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCode as CoreAuthorizationCode;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeId;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeRepository as AuthorizationCodeRepositoryInterface;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;

final class AuthorizationCodeRepository implements AuthorizationCodeRepositoryInterface, ServiceEntityRepositoryInterface
{
    private $entityRepository;
    private $entityManager;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->entityManager = $managerRegistry->getManagerForClass(AuthorizationCode::class);
        $this->entityRepository = $this->entityManager->getRepository(AuthorizationCode::class);
    }

    public function find(AuthorizationCodeId $authorizationCodeId): ?CoreAuthorizationCode
    {
        return $this->entityRepository->find($authorizationCodeId);
    }

    public function save(CoreAuthorizationCode $accessToken): void
    {
        Assertion::isInstanceOf($accessToken, AuthorizationCode::class, 'Unsupported authorization code class');
        $this->entityManager->persist($accessToken);
        $this->entityManager->flush();
    }

    public function create(ClientId $clientId, UserAccountId $userAccountId, array $queryParameters, string $redirectUri, \DateTimeImmutable $expiresAt, DataBag $parameter, DataBag $metadata, ?ResourceServerId $resourceServerId): CoreAuthorizationCode
    {
        return new AuthorizationCode(
            new AuthorizationCodeId(\bin2hex(\random_bytes(32))),
            $clientId,
            $userAccountId,
            $queryParameters,
            $redirectUri,
            $expiresAt,
            $parameter,
            $metadata,
            $resourceServerId
        );
    }
}
