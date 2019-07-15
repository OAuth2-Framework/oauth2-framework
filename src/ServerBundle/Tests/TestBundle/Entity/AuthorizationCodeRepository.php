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

namespace OAuth2Framework\ServerBundle\Tests\TestBundle\Entity;

use Assert\Assertion;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCode as AuthorizationCodeInterface;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeId;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeRepository as AuthorizationCodeRepositoryInterface;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use Psr\Cache\CacheItemPoolInterface;

final class AuthorizationCodeRepository implements AuthorizationCodeRepositoryInterface
{
    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
        $this->load();
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
            new AuthorizationCodeId(bin2hex(random_bytes(32))),
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

    private function load(): void
    {
        foreach ($this->getData() as $datum) {
            $authorizationCode = new AuthorizationCode(
                new AuthorizationCodeId($datum['authorization_code_id']),
                new ClientId($datum['client_id']),
                new UserAccountId($datum['user_account_id']),
                $datum['query_parameters'],
                $datum['redirect_uri'],
                $datum['expires_at'],
                new DataBag($datum['parameter']),
                new DataBag($datum['metadata']),
                $datum['resource_server_id']
            );
            if ($datum['is_revoked']) {
                $authorizationCode->markAsRevoked();
            }
            if ($datum['is_used']) {
                $authorizationCode->markAsUsed();
            }
            $this->save($authorizationCode);
        }
    }

    private function getData(): array
    {
        return [
            [
                'authorization_code_id' => 'VALID_AUTHORIZATION_CODE',
                'client_id' => 'CLIENT_ID_3',
                'user_account_id' => 'john.1',
                'query_parameters' => [],
                'redirect_uri' => 'http://localhost/callback',
                'expires_at' => new \DateTimeImmutable('now +1 day'),
                'parameter' => [],
                'metadata' => [],
                'resource_server_id' => null,
                'is_revoked' => false,
                'is_used' => false,
            ],
            [
                'authorization_code_id' => 'VALID_AUTHORIZATION_CODE_FOR_CONFIDENTIAL_CLIENT',
                'client_id' => 'CLIENT_ID_5',
                'user_account_id' => 'john.1',
                'query_parameters' => [],
                'redirect_uri' => 'http://localhost/callback',
                'expires_at' => new \DateTimeImmutable('now +1 day'),
                'parameter' => [],
                'metadata' => [],
                'resource_server_id' => null,
                'is_revoked' => false,
                'is_used' => false,
            ],
            [
                'authorization_code_id' => 'REVOKED_AUTHORIZATION_CODE',
                'client_id' => 'CLIENT_ID_3',
                'user_account_id' => 'john.1',
                'query_parameters' => [],
                'redirect_uri' => 'http://localhost/callback',
                'expires_at' => new \DateTimeImmutable('now +1 day'),
                'parameter' => [],
                'metadata' => [],
                'resource_server_id' => null,
                'is_revoked' => true,
                'is_used' => false,
            ],
            [
                'authorization_code_id' => 'EXPIRED_AUTHORIZATION_CODE',
                'client_id' => 'CLIENT_ID_3',
                'user_account_id' => 'john.1',
                'query_parameters' => [],
                'redirect_uri' => 'http://localhost/callback',
                'expires_at' => new \DateTimeImmutable('now -1 day'),
                'parameter' => [],
                'metadata' => [],
                'resource_server_id' => null,
                'is_revoked' => false,
                'is_used' => false,
            ],
            [
                'authorization_code_id' => 'USED_AUTHORIZATION_CODE',
                'client_id' => 'CLIENT_ID_3',
                'user_account_id' => 'john.1',
                'query_parameters' => [],
                'redirect_uri' => 'http://localhost/callback',
                'expires_at' => new \DateTimeImmutable('now +1 day'),
                'parameter' => [],
                'metadata' => [],
                'resource_server_id' => null,
                'is_revoked' => false,
                'is_used' => true,
            ],
        ];
    }
}
