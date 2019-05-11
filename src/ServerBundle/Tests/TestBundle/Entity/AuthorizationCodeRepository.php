<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace OAuth2Framework\ServerBundle\Tests\TestBundle\Entity;

use Assert\Assertion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepositoryInterface;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCode as AuthorizationCodeInterface;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeId;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeRepository as AuthorizationCodeRepositoryInterface;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use Psr\Cache\CacheItemPoolInterface;

final class AuthorizationCodeRepository implements AuthorizationCodeRepositoryInterface, ServiceEntityRepositoryInterface
{
    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    public function find(AuthorizationCodeId $authorizationCodeId): ?AuthorizationCodeInterface
    {
        $item = $this->cache->getItem('AuthorizationCode-'.$authorizationCodeId->getValue());
        if ($item->isHit()) {
            return $item->get();
        }

        return null;
    }

    public function save(AuthorizationCodeInterface $authorizationCode): void
    {
        Assertion::isInstanceOf($authorizationCode, AuthorizationCode::class, 'Unsupported authorization code class');
        $item = $this->cache->getItem('AuthorizationCode-'.$authorizationCode->getId()->getValue());
        $item->set($authorizationCode);
        $this->cache->save($item);
    }

    public function create(ClientId $clientId, UserAccountId $userAccountId, array $queryParameters, string $redirectUri, \DateTimeImmutable $expiresAt, DataBag $parameter, DataBag $metadata, ?ResourceServerId $resourceServerId): AuthorizationCodeInterface
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
