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

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepositoryInterface;
use Doctrine\Common\Persistence\ManagerRegistry;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshToken as CoreRefreshToken;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenId;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenRepository as RefreshTokenRepositoryInterface;

final class RefreshTokenRepository implements RefreshTokenRepositoryInterface, ServiceEntityRepositoryInterface
{
    private $entityRepository;
    private $entityManager;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->entityManager = $managerRegistry->getManagerForClass(RefreshToken::class);
        $this->entityRepository = $this->entityManager->getRepository(RefreshToken::class);
    }

    public function find(RefreshTokenId $refreshTokenId): ?CoreRefreshToken
    {
        return $this->entityRepository->find($refreshTokenId);
    }

    public function save(CoreRefreshToken $refreshToken): void
    {
        if (!$refreshToken instanceof RefreshToken) {
            throw new \InvalidArgumentException('Unsupported refresh token class');
        }
        $this->entityManager->persist($refreshToken);
        $this->entityManager->flush();
    }

    public function create(ClientId $clientId, ResourceOwnerId $resourceOwnerId, \DateTimeImmutable $expiresAt, DataBag $parameter, DataBag $metadata, ?ResourceServerId $resourceServerId): CoreRefreshToken
    {
        return new RefreshToken(
            new RefreshTokenId(\bin2hex(\random_bytes(32))),
            $clientId,
            $resourceOwnerId,
            $expiresAt,
            $parameter,
            $metadata,
            $resourceServerId
        );
    }
}
