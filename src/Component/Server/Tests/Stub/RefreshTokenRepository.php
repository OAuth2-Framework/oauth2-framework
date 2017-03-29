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

use Base64Url\Base64Url;
use OAuth2Framework\Component\Server\Model\Client\ClientId;
use OAuth2Framework\Component\Server\Model\DataBag\DataBag;
use OAuth2Framework\Component\Server\Model\Event\EventStoreInterface;
use OAuth2Framework\Component\Server\Model\RefreshToken\RefreshToken;
use OAuth2Framework\Component\Server\Model\RefreshToken\RefreshTokenId;
use OAuth2Framework\Component\Server\Model\RefreshToken\RefreshTokenRepositoryInterface;
use OAuth2Framework\Component\Server\Model\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Server\Model\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;
use SimpleBus\Message\Recorder\RecordsMessages;

final class RefreshTokenRepository implements RefreshTokenRepositoryInterface
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
     * RefreshTokenRepository constructor.
     *
     * @param EventStoreInterface $eventStore
     * @param RecordsMessages     $eventRecorder
     * @param string              $lifetime
     */
    public function __construct(EventStoreInterface $eventStore, RecordsMessages $eventRecorder, string $lifetime)
    {
        $this->eventStore = $eventStore;
        $this->eventRecorder = $eventRecorder;
        $this->lifetime = $lifetime;
        $this->createAndSaveRefreshToken(
            RefreshTokenId::create('EXPIRED_REFRESH_TOKEN'),
            UserAccountId::create('john.1'),
            ClientId::create('client1'),
            new \DateTimeImmutable('now -2 day'),
            DataBag::createFromArray([]),
            DataBag::createFromArray([])
        );

        $this->createAndSaveRefreshToken(
            RefreshTokenId::create('VALID_REFRESH_TOKEN'),
            UserAccountId::create('john.1'),
            ClientId::create('client1'),
            new \DateTimeImmutable('now +2 day'),
            DataBag::createFromArray([]),
            DataBag::createFromArray([])
        );

        $this->createRevokeAndSaveRefreshToken(
            RefreshTokenId::create('REVOKED_REFRESH_TOKEN'),
            UserAccountId::create('john.1'),
            ClientId::create('client1'),
            new \DateTimeImmutable('now +2 day'),
            DataBag::createFromArray([]),
            DataBag::createFromArray([]),
            [],
            ResourceServerId::create('ResourceServer1')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function create(ResourceOwnerId $resourceOwnerId, ClientId $clientId, DataBag $parameters, DataBag $metadatas, array $scopes, ResourceServerId $resourceServerId = null, \DateTimeImmutable $expiresAt = null): RefreshToken
    {
        if (null === $expiresAt) {
            $expiresAt = new \DateTimeImmutable($this->lifetime);
        }

        $refreshToken = RefreshToken::createEmpty();
        $refreshToken = $refreshToken->create(
            RefreshTokenId::create(Base64Url::encode(random_bytes(50))),
            $resourceOwnerId,
            $clientId,
            $parameters,
            $metadatas,
            $scopes,
            $expiresAt,
            $resourceServerId
        );

        return $refreshToken;
    }

    /**
     * {@inheritdoc}
     */
    public function save(RefreshToken $refreshToken)
    {
        $events = $refreshToken->recordedMessages();
        foreach ($events as $event) {
            $this->eventStore->save($event);
            $this->eventRecorder->record($event);
        }
        $refreshToken->eraseMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function find(RefreshTokenId $refreshTokenId)
    {
        $refreshToken = null;
        $events = $this->eventStore->getEvents($refreshTokenId);
        if (!empty($events)) {
            $refreshToken = RefreshToken::createEmpty();
            foreach ($events as $event) {
                $refreshToken = $refreshToken->apply($event);
            }
        }

        return $refreshToken;
    }

    /**
     * @param RefreshTokenId          $refreshTokenId
     * @param UserAccountId           $userAccountId
     * @param ClientId                $clientId
     * @param \DateTimeImmutable|null $expiresAt
     * @param DataBag                 $parameters
     * @param DataBag                 $metadatas
     * @param array                   $scopes
     * @param ResourceServerId|null   $resourceServerId
     */
    private function createAndSaveRefreshToken(RefreshTokenId $refreshTokenId, UserAccountId $userAccountId, ClientId $clientId, \DateTimeImmutable $expiresAt = null, DataBag $parameters, DataBag $metadatas, array $scopes = [], ResourceServerId $resourceServerId = null)
    {
        $refreshToken = RefreshToken::createEmpty();
        $refreshToken = $refreshToken->create(
            $refreshTokenId,
            $userAccountId,
            $clientId,
            $parameters,
            $metadatas,
            $scopes,
            $expiresAt,
            $resourceServerId
        );
        $events = $refreshToken->recordedMessages();
        foreach ($events as $event) {
            $this->eventStore->save($event);
        }
        $refreshToken->eraseMessages();
        $this->save($refreshToken);
    }

    /**
     * @param RefreshTokenId          $refreshTokenId
     * @param UserAccountId           $userAccountId
     * @param ClientId                $clientId
     * @param \DateTimeImmutable|null $expiresAt
     * @param DataBag                 $parameters
     * @param DataBag                 $metadatas
     * @param array                   $scopes
     * @param ResourceServerId|null   $resourceServerId
     */
    private function createRevokeAndSaveRefreshToken(RefreshTokenId $refreshTokenId, UserAccountId $userAccountId, ClientId $clientId, \DateTimeImmutable $expiresAt = null, DataBag $parameters, DataBag $metadatas, array $scopes = [], ResourceServerId $resourceServerId = null)
    {
        $refreshToken = RefreshToken::createEmpty();
        $refreshToken = $refreshToken->create(
            $refreshTokenId,
            $userAccountId,
            $clientId,
            $parameters,
            $metadatas,
            $scopes,
            $expiresAt,
            $resourceServerId
        );
        $refreshToken = $refreshToken->markAsRevoked();
        $events = $refreshToken->recordedMessages();
        foreach ($events as $event) {
            $this->eventStore->save($event);
        }
        $refreshToken->eraseMessages();
        $this->save($refreshToken);
    }
}
