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

use OAuth2Framework\Component\Server\Model\AuthCode\AuthCode;
use OAuth2Framework\Component\Server\Model\AuthCode\AuthCodeId;
use OAuth2Framework\Component\Server\Model\AuthCode\AuthCodeRepositoryInterface;
use OAuth2Framework\Component\Server\Model\Client\ClientId;
use OAuth2Framework\Component\Server\Model\DataBag\DataBag;
use OAuth2Framework\Component\Server\Model\Event\EventStoreInterface;
use OAuth2Framework\Component\Server\Model\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;
use SimpleBus\Message\Recorder\RecordsMessages;

final class AuthCodeRepository implements AuthCodeRepositoryInterface
{
    /**
     * @var RecordsMessages
     */
    private $eventRecorder;

    /**
     * @var EventStoreInterface
     */
    private $eventStore;

    /**
     * @var string
     */
    private $lifetime;

    /**
     * AuthCodeRepository constructor.
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
        $this->createAndSaveAuthCode(
            AuthCodeId::create('VALID_AUTH_CODE'),
            ClientId::create('client1'),
            UserAccountId::create('john.1'),
            [],
            'https://www.example.com/callback',
            new \DateTimeImmutable('now +1 day'),
            DataBag::createFromArray([]),
            DataBag::createFromArray([]),
            ['openid', 'email', 'phone', 'address'],
            false
        );

        $this->createAndSaveAuthCode(
            AuthCodeId::create('EXPIRED_AUTH_CODE'),
            ClientId::create('client1'),
            UserAccountId::create('john.1'),
            [],
            'https://www.example.com/callback',
            new \DateTimeImmutable('now -1 day'),
            DataBag::createFromArray([]),
            DataBag::createFromArray([]),
            ['openid', 'email', 'phone', 'address'],
            false
        );

        $this->createRevokedAndSaveAuthCode(
            AuthCodeId::create('REVOKED_AUTH_CODE'),
            ClientId::create('client1'),
            UserAccountId::create('john.1'),
            [],
            'https://www.example.com/callback',
            new \DateTimeImmutable('now +1 day'),
            DataBag::createFromArray([]),
            DataBag::createFromArray([]),
            ['openid', 'email', 'phone', 'address'],
            false
        );

        $this->createUsedAndSaveAuthCode(
            AuthCodeId::create('USED_AUTH_CODE'),
            ClientId::create('client1'),
            UserAccountId::create('john.1'),
            [],
            'https://www.example.com/callback',
            new \DateTimeImmutable('now +1 day'),
            DataBag::createFromArray([]),
            DataBag::createFromArray([]),
            ['openid', 'email', 'phone', 'address'],
            false
        );

        $this->createAndSaveAuthCode(
            AuthCodeId::create('AUTH_CODE_WITH_CODE_VERIFIER_PLAIN'),
            ClientId::create('client1'),
            UserAccountId::create('john.1'),
            [
                'code_challenge' => 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM',
                'code_challenge_method' => 'plain',
            ],
            'https://www.example.com/callback',
            new \DateTimeImmutable('now +1 day'),
            DataBag::createFromArray([]),
            DataBag::createFromArray([]),
            ['openid', 'email', 'phone', 'address'],
            false
        );

        $this->createAndSaveAuthCode(
            AuthCodeId::create('AUTH_CODE_WITH_CODE_VERIFIER_S256'),
            ClientId::create('client1'),
            UserAccountId::create('john.1'),
            [
                'code_challenge' => 'DSmbHrVIcI0EU05-BQxCe1bt-hXRNjejSEvdYbq_g4Q',
                'code_challenge_method' => 'S256',
            ],
            'https://www.example.com/callback',
            new \DateTimeImmutable('now +1 day'),
            DataBag::createFromArray([]),
            DataBag::createFromArray([]),
            ['openid', 'email', 'phone', 'address'],
            false
        );
    }

    /**
     * {@inheritdoc}
     */
    public function create(ClientId $clientId, UserAccountId $userAccountId, array $queryParameters, string $redirectUri, DataBag $parameters, DataBag $metadatas, array $scopes, bool $withRefreshToken, ?ResourceServerId $resourceServerId, ?\DateTimeImmutable $expiresAt): AuthCode
    {
        if (null === $expiresAt) {
            $expiresAt = new \DateTimeImmutable($this->lifetime);
        }

        $authCode = AuthCode::createEmpty();
        $authCode = $authCode->create(
            AuthCodeId::create(bin2hex(random_bytes(50))),
            $clientId,
            $userAccountId,
            $queryParameters,
            $redirectUri,
            $expiresAt,
            $parameters,
            $metadatas,
            $scopes,
            $withRefreshToken,
            $resourceServerId
        );

        return $authCode;
    }

    /**
     * @param AuthCode $authCode
     */
    public function save(AuthCode $authCode)
    {
        $events = $authCode->recordedMessages();
        foreach ($events as $event) {
            $this->eventStore->save($event);
            $this->eventRecorder->record($event);
        }
        $authCode->eraseMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function find(AuthCodeId $authCodeId): ?AuthCode
    {
        $authCode = null;
        $events = $this->eventStore->getEvents($authCodeId);
        if (!empty($events)) {
            $authCode = AuthCode::createEmpty();
            foreach ($events as $event) {
                $authCode = $authCode->apply($event);
            }
        }

        return $authCode;
    }

    /**
     * @param AuthCodeId              $authCodeId
     * @param UserAccountId           $userAccountId
     * @param ClientId                $clientId
     * @param array                   $queryParameters
     * @param string                  $redirectUri
     * @param DataBag                 $parameters
     * @param DataBag                 $metadatas
     * @param array                   $scopes
     * @param \DateTimeImmutable|null $expiresAt
     * @param bool                    $withRefreshToken
     * @param ResourceServerId|null   $resourceServerId
     */
    private function createAndSaveAuthCode(AuthCodeId $authCodeId, ClientId $clientId, UserAccountId $userAccountId, array $queryParameters, string $redirectUri, \DateTimeImmutable $expiresAt = null, DataBag $parameters, DataBag $metadatas, array $scopes = [], bool $withRefreshToken = false, ResourceServerId $resourceServerId = null)
    {
        $authCode = AuthCode::createEmpty();
        $authCode = $authCode->create(
            $authCodeId,
            $clientId,
            $userAccountId,
            $queryParameters,
            $redirectUri,
            $expiresAt,
            $parameters,
            $metadatas,
            $scopes,
            $withRefreshToken,
            $resourceServerId
        );
        $events = $authCode->recordedMessages();
        foreach ($events as $event) {
            $this->eventStore->save($event);
        }
        $authCode->eraseMessages();
        $this->save($authCode);
    }

    /**
     * @param AuthCodeId              $authCodeId
     * @param UserAccountId           $userAccountId
     * @param ClientId                $clientId
     * @param array                   $queryParameters
     * @param string                  $redirectUri
     * @param DataBag                 $parameters
     * @param DataBag                 $metadatas
     * @param array                   $scopes
     * @param \DateTimeImmutable|null $expiresAt
     * @param bool                    $withRefreshToken
     * @param ResourceServerId|null   $resourceServerId
     */
    private function createRevokedAndSaveAuthCode(AuthCodeId $authCodeId, ClientId $clientId, UserAccountId $userAccountId, array $queryParameters, string $redirectUri, \DateTimeImmutable $expiresAt = null, DataBag $parameters, DataBag $metadatas, array $scopes = [], bool $withRefreshToken = false, ResourceServerId $resourceServerId = null)
    {
        $authCode = AuthCode::createEmpty();
        $authCode = $authCode->create(
            $authCodeId,
            $clientId,
            $userAccountId,
            $queryParameters,
            $redirectUri,
            $expiresAt,
            $parameters,
            $metadatas,
            $scopes,
            $withRefreshToken,
            $resourceServerId
        );
        $authCode = $authCode->markAsRevoked();
        $events = $authCode->recordedMessages();
        foreach ($events as $event) {
            $this->eventStore->save($event);
        }
        $authCode->eraseMessages();
        $this->save($authCode);
    }

    /**
     * @param AuthCodeId              $authCodeId
     * @param UserAccountId           $userAccountId
     * @param ClientId                $clientId
     * @param array                   $queryParameters
     * @param string                  $redirectUri
     * @param DataBag                 $parameters
     * @param DataBag                 $metadatas
     * @param array                   $scopes
     * @param \DateTimeImmutable|null $expiresAt
     * @param bool                    $withRefreshToken
     * @param ResourceServerId|null   $resourceServerId
     */
    private function createUsedAndSaveAuthCode(AuthCodeId $authCodeId, ClientId $clientId, UserAccountId $userAccountId, array $queryParameters, string $redirectUri, \DateTimeImmutable $expiresAt = null, DataBag $parameters, DataBag $metadatas, array $scopes = [], bool $withRefreshToken = false, ResourceServerId $resourceServerId = null)
    {
        $authCode = AuthCode::createEmpty();
        $authCode = $authCode->create(
            $authCodeId,
            $clientId,
            $userAccountId,
            $queryParameters,
            $redirectUri,
            $expiresAt,
            $parameters,
            $metadatas,
            $scopes,
            $withRefreshToken,
            $resourceServerId
        );
        $authCode = $authCode->markAsUsed();
        $events = $authCode->recordedMessages();
        foreach ($events as $event) {
            $this->eventStore->save($event);
        }
        $authCode->eraseMessages();
        $this->save($authCode);
    }
}
