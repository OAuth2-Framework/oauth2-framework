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

use OAuth2Framework\Component\Server\Model\Event\EventStoreInterface;
use OAuth2Framework\Component\Server\Model\InitialAccessToken\InitialAccessToken;
use OAuth2Framework\Component\Server\Model\InitialAccessToken\InitialAccessTokenId;
use OAuth2Framework\Component\Server\Model\InitialAccessToken\InitialAccessTokenRepositoryInterface;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;
use Ramsey\Uuid\Uuid;
use SimpleBus\Message\Recorder\RecordsMessages;

final class InitialAccessTokenRepository implements InitialAccessTokenRepositoryInterface
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
     * InitialAccessTokenRepository constructor.
     *
     * @param EventStoreInterface $eventStore
     * @param RecordsMessages     $eventRecorder
     */
    public function __construct(EventStoreInterface $eventStore, RecordsMessages $eventRecorder)
    {
        $this->eventStore = $eventStore;
        $this->eventRecorder = $eventRecorder;
        $this->createAndSaveInitialAccessToken(
            InitialAccessTokenId::create('INITIAL_ACCESS_TOKEN_VALID'),
            null, //UserAccountId::create('user1'),
            new \DateTimeImmutable('now +1 hour')
        );

        $this->createAndSaveInitialAccessToken(
            InitialAccessTokenId::create('INITIAL_ACCESS_TOKEN_EXPIRED'),
            null, //UserAccountId::create('user1'),
            new \DateTimeImmutable('now -1 hour')
        );

        $this->createRevokeAndSaveInitialAccessToken(
            InitialAccessTokenId::create('INITIAL_ACCESS_TOKEN_REVOKED'),
            null, //UserAccountId::create('user1'),
            new \DateTimeImmutable('now +1 hour')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function create(UserAccountId $userAccountId = null, \DateTimeImmutable $expiresAt = null): InitialAccessToken
    {
        $initialAccessTokeId = InitialAccessTokenId::create(Uuid::uuid4()->toString());
        $initialAccessToken = InitialAccessToken::createEmpty();
        $initialAccessToken = $initialAccessToken->create($initialAccessTokeId, $userAccountId, $expiresAt);

        return $initialAccessToken;
    }

    /**
     * {@inheritdoc}
     */
    public function save(InitialAccessToken $initialAccessToken)
    {
        $events = $initialAccessToken->recordedMessages();
        foreach ($events as $event) {
            $this->eventStore->save($event);
            $this->eventRecorder->record($event);
        }
        $initialAccessToken->eraseMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function find(InitialAccessTokenId $initialAccessTokenId): ?InitialAccessToken
    {
        $initialAccessToken = null;
        $events = $this->eventStore->getEvents($initialAccessTokenId);
        if (!empty($events)) {
            $initialAccessToken = InitialAccessToken::createEmpty();
            foreach ($events as $event) {
                $initialAccessToken = $initialAccessToken->apply($event);
            }
        }

        return $initialAccessToken;
    }

    /**
     * @param InitialAccessTokenId    $initialAccessTokenId
     * @param UserAccountId|null      $userAccountId
     * @param \DateTimeImmutable|null $expiresAt
     */
    private function createAndSaveInitialAccessToken(InitialAccessTokenId $initialAccessTokenId, ?UserAccountId $userAccountId, ?\DateTimeImmutable $expiresAt)
    {
        $initialAccessToken = InitialAccessToken::createEmpty();
        $initialAccessToken = $initialAccessToken->create(
            $initialAccessTokenId,
            $userAccountId,
            $expiresAt
        );
        $events = $initialAccessToken->recordedMessages();
        foreach ($events as $event) {
            $this->eventStore->save($event);
        }
        $initialAccessToken->eraseMessages();
        $this->save($initialAccessToken);
    }

    /**
     * @param InitialAccessTokenId    $initialAccessTokenId
     * @param UserAccountId|null      $userAccountId
     * @param \DateTimeImmutable|null $expiresAt
     */
    private function createRevokeAndSaveInitialAccessToken(InitialAccessTokenId $initialAccessTokenId, ?UserAccountId $userAccountId, ?\DateTimeImmutable $expiresAt)
    {
        $initialAccessToken = InitialAccessToken::createEmpty();
        $initialAccessToken = $initialAccessToken->create(
            $initialAccessTokenId,
            $userAccountId,
            $expiresAt
        );
        $initialAccessToken = $initialAccessToken->markAsRevoked();
        $events = $initialAccessToken->recordedMessages();
        foreach ($events as $event) {
            $this->eventStore->save($event);
        }
        $initialAccessToken->eraseMessages();
        $this->save($initialAccessToken);
    }
}
