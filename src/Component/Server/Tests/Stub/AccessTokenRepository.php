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

namespace OAuth2Framework\Component\Server\Tests\Stub;

use OAuth2Framework\Component\Server\Model\AccessToken\AccessToken;
use OAuth2Framework\Component\Server\Model\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Server\Model\AccessToken\AccessTokenRepositoryInterface;
use OAuth2Framework\Component\Server\Model\Client\ClientId;
use OAuth2Framework\Component\Server\Model\DataBag\DataBag;
use OAuth2Framework\Component\Server\Model\Event\EventStoreInterface;
use OAuth2Framework\Component\Server\Model\RefreshToken\RefreshTokenId;
use OAuth2Framework\Component\Server\Model\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Server\Model\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;
use SimpleBus\Message\Recorder\RecordsMessages;

final class AccessTokenRepository implements AccessTokenRepositoryInterface
{
    /**
     * @var EventStoreInterface
     */
    private $eventStore;

    /**
     * @var RecordsMessages
     */
    private $eventRecorder;

    /**
     * @var string
     */
    private $lifetime;

    /**
     * AccessTokenRepository constructor.
     *
     *
     * @param EventStoreInterface $eventStore
     * @param RecordsMessages     $eventRecorder
     * @param string              $lifetime
     */
    public function __construct(EventStoreInterface $eventStore, RecordsMessages $eventRecorder, string $lifetime)
    {
        $this->eventStore = $eventStore;
        $this->lifetime = $lifetime;
        $this->eventRecorder = $eventRecorder;

        $this->createAndSaveAccessToken(
            AccessTokenId::create('ACCESS_TOKEN_#1'),
            UserAccountId::create('john.1'),
            ClientId::create('client1'),
            DataBag::createFromArray(['token_type' => 'Bearer']),
            DataBag::createFromArray([]),
            [],
            new \DateTimeImmutable('now +3600 seconds'),
            null,
            ResourceServerId::create('ResourceServer1')
        );

        $this->createRevokedAndSaveAccessToken(
            AccessTokenId::create('REVOKED_ACCESS_TOKEN'),
            UserAccountId::create('john.1'),
            ClientId::create('client1'),
            DataBag::createFromArray(['token_type' => 'Bearer']),
            DataBag::createFromArray([]),
            [],
            new \DateTimeImmutable('now +3600 seconds')
        );

        $this->createAndSaveAccessToken(
            AccessTokenId::create('ACCESS_TOKEN_#2'),
            UserAccountId::create('john.1'),
            ClientId::create('client2'),
            DataBag::createFromArray([]),
            DataBag::createFromArray([]),
            [],
            new \DateTimeImmutable('now +3600 seconds')
        );

        $this->createAndSaveAccessToken(
            AccessTokenId::create('VALID_ACCESS_TOKEN_FOR_USERINFO'),
            UserAccountId::create('john.1'),
            ClientId::create('client1'),
            DataBag::createFromArray(['token_type' => 'Bearer']),
            DataBag::createFromArray(['redirect_uri' => 'http://127.0.0.1:8080']),
            ['openid', 'profile', 'email', 'phone', 'address'],
            new \DateTimeImmutable('now +3600 seconds')
        );

        $this->createAndSaveAccessToken(
            AccessTokenId::create('VALID_ACCESS_TOKEN_FOR_SIGNED_USERINFO'),
            UserAccountId::create('john.1'),
            ClientId::create('client2'),
            DataBag::createFromArray(['token_type' => 'Bearer']),
            DataBag::createFromArray(['redirect_uri' => 'http://127.0.0.1:8080']),
            ['openid', 'profile', 'email', 'phone', 'address'],
            new \DateTimeImmutable('now +3600 seconds')
        );

        $this->createAndSaveAccessToken(
            AccessTokenId::create('INVALID_ACCESS_TOKEN_FOR_USERINFO'),
            UserAccountId::create('john.1'),
            ClientId::create('client2'),
            DataBag::createFromArray(['token_type' => 'Bearer']),
            DataBag::createFromArray(['redirect_uri' => 'http://127.0.0.1:8080']),
            [],
            new \DateTimeImmutable('now +3600 seconds')
        );

        $this->createAndSaveAccessToken(
            AccessTokenId::create('ACCESS_TOKEN_ISSUED_THROUGH_TOKEN_ENDPOINT'),
            UserAccountId::create('john.1'),
            ClientId::create('client2'),
            DataBag::createFromArray(['token_type' => 'Bearer']),
            DataBag::createFromArray([]),
            ['openid', 'profile', 'email', 'phone', 'address'],
            new \DateTimeImmutable('now +3600 seconds')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function find(AccessTokenId $accessTokenId)
    {
        $accessToken = null;
        $events = $this->eventStore->getEvents($accessTokenId);
        if (!empty($events)) {
            $accessToken = AccessToken::createEmpty();
            foreach ($events as $event) {
                $accessToken = $accessToken->apply($event);
            }
        }

        return $accessToken;
    }

    /**
     * @param AccessToken $accessToken
     */
    public function save(AccessToken $accessToken)
    {
        $events = $accessToken->recordedMessages();
        foreach ($events as $event) {
            $this->eventStore->save($event);
            $this->eventRecorder->record($event);
        }
        $accessToken->eraseMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function create(ResourceOwnerId $resourceOwnerId, ClientId $clientId, DataBag $parameters, DataBag $metadatas, array $scopes, RefreshTokenId $refreshTokenId = null, ResourceServerId $resourceServerId = null, \DateTimeImmutable $expiresAt = null): AccessToken
    {
        if (null === $expiresAt) {
            $expiresAt = new \DateTimeImmutable($this->lifetime);
        }

        $accessToken = AccessToken::createEmpty();
        $accessToken = $accessToken->create(
            AccessTokenId::create(bin2hex(random_bytes(50))),
            $resourceOwnerId,
            $clientId,
            $parameters,
            $metadatas,
            $scopes,
            $expiresAt,
            $refreshTokenId,
            $resourceServerId
        );

        return $accessToken;
    }

    /**
     * @param AccessTokenId           $accessTokenId
     * @param UserAccountId           $userAccountId
     * @param ClientId                $clientId
     * @param DataBag                 $parameters
     * @param DataBag                 $metadatas
     * @param array                   $scopes
     * @param \DateTimeImmutable|null $expiresAt
     * @param RefreshTokenId|null     $refreshTokenId
     * @param ResourceServerId|null   $resourceServerId
     */
    private function createAndSaveAccessToken(AccessTokenId $accessTokenId, UserAccountId $userAccountId, ClientId $clientId, DataBag $parameters, DataBag $metadatas, array $scopes = [], \DateTimeImmutable $expiresAt = null, RefreshTokenId $refreshTokenId = null, ResourceServerId $resourceServerId = null)
    {
        $accessToken = AccessToken::createEmpty();
        $accessToken = $accessToken->create(
            $accessTokenId,
            $userAccountId,
            $clientId,
            $parameters,
            $metadatas,
            $scopes,
            $expiresAt,
            $refreshTokenId,
            $resourceServerId
        );
        $events = $accessToken->recordedMessages();
        foreach ($events as $event) {
            $this->eventStore->save($event);
        }
        $accessToken->eraseMessages();
        $this->save($accessToken);
    }

    /**
     * @param AccessTokenId           $accessTokenId
     * @param UserAccountId           $userAccountId
     * @param ClientId                $clientId
     * @param DataBag                 $parameters
     * @param DataBag                 $metadatas
     * @param array                   $scopes
     * @param \DateTimeImmutable|null $expiresAt
     * @param RefreshTokenId|null     $refreshTokenId
     * @param ResourceServerId|null   $resourceServerId
     */
    private function createRevokedAndSaveAccessToken(AccessTokenId $accessTokenId, UserAccountId $userAccountId, ClientId $clientId, DataBag $parameters, DataBag $metadatas, array $scopes = [], \DateTimeImmutable $expiresAt = null, RefreshTokenId $refreshTokenId = null, ResourceServerId $resourceServerId = null)
    {
        $accessToken = AccessToken::createEmpty();
        $accessToken = $accessToken->create(
            $accessTokenId,
            $userAccountId,
            $clientId,
            $parameters,
            $metadatas,
            $scopes,
            $expiresAt,
            $refreshTokenId,
            $resourceServerId
        );
        $accessToken = $accessToken->markAsRevoked();
        $events = $accessToken->recordedMessages();
        foreach ($events as $event) {
            $this->eventStore->save($event);
        }
        $accessToken->eraseMessages();
        $this->save($accessToken);
    }
}
