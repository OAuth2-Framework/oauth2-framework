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

namespace OAuth2Framework\Component\Server\Event\AuthCode;

use OAuth2Framework\Component\Server\Model\AuthCode\AuthCodeId;
use OAuth2Framework\Component\Server\Model\Client\ClientId;
use OAuth2Framework\Component\Server\Model\DataBag\DataBag;
use OAuth2Framework\Component\Server\Model\Event\Event;
use OAuth2Framework\Component\Server\Model\Event\EventId;
use OAuth2Framework\Component\Server\Model\Id\Id;
use OAuth2Framework\Component\Server\Model\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;
use OAuth2Framework\Component\Server\Schema\DomainObjectInterface;

final class AuthCodeCreatedEvent extends Event
{
    /**
     * @var AuthCodeId
     */
    private $authCodeId;

    /**
     * @var \DateTimeImmutable
     */
    private $expiresAt;

    /**
     * @var UserAccountId
     */
    private $userAccountId;

    /**
     * @var ClientId
     */
    private $clientId;

    /**
     * @var DataBag
     */
    private $parameters;

    /**
     * @var DataBag
     */
    private $metadatas;

    /**
     * @var string[]
     */
    private $scopes;

    /**
     * @var array
     */
    private $queryParameters;

    /**
     * @var string
     */
    private $redirectUri;

    /**
     * @var bool
     */
    private $withRefreshToken;

    /**
     * @var ResourceServerId|null
     */
    private $resourceServerId;

    /**
     * AuthCodeCreatedEvent constructor.
     *
     * @param AuthCodeId              $authCodeId
     * @param ClientId                $clientId
     * @param UserAccountId           $userAccountId
     * @param array                   $queryParameters
     * @param string                  $redirectUri
     * @param \DateTimeImmutable      $expiresAt
     * @param DataBag                 $parameters
     * @param DataBag                 $metadatas
     * @param array                   $scopes
     * @param bool                    $withRefreshToken
     * @param ResourceServerId|null   $resourceServerId
     * @param \DateTimeImmutable|null $recordedOn
     * @param EventId|null            $eventId
     */
    protected function __construct(AuthCodeId $authCodeId, ClientId $clientId, UserAccountId $userAccountId, array $queryParameters, string $redirectUri, \DateTimeImmutable $expiresAt, DataBag $parameters, DataBag $metadatas, array $scopes, bool $withRefreshToken, ? ResourceServerId $resourceServerId, ? \DateTimeImmutable $recordedOn, ? EventId $eventId)
    {
        parent::__construct($recordedOn, $eventId);
        $this->authCodeId = $authCodeId;
        $this->userAccountId = $userAccountId;
        $this->clientId = $clientId;
        $this->expiresAt = $expiresAt;
        $this->parameters = $parameters;
        $this->metadatas = $metadatas;
        $this->scopes = $scopes;
        $this->redirectUri = $redirectUri;
        $this->queryParameters = $queryParameters;
        $this->withRefreshToken = $withRefreshToken;
        $this->resourceServerId = $resourceServerId;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSchema(): string
    {
        return 'https://oauth2-framework.spomky-labs.com/schemas/events/auth-code/created/1.0/schema';
    }

    /**
     * @param AuthCodeId            $authCodeId
     * @param ClientId              $clientId
     * @param UserAccountId         $userAccountId
     * @param array                 $queryParameters
     * @param string                $redirectUri
     * @param \DateTimeImmutable    $expiresAt
     * @param DataBag               $parameters
     * @param DataBag               $metadatas
     * @param array                 $scopes
     * @param bool                  $withRefreshToken
     * @param ResourceServerId|null $resourceServerId
     *
     * @return AuthCodeCreatedEvent
     */
    public static function create(AuthCodeId $authCodeId, ClientId $clientId, UserAccountId $userAccountId, array $queryParameters, string $redirectUri, \DateTimeImmutable $expiresAt, DataBag $parameters, DataBag $metadatas, array $scopes, bool $withRefreshToken, ? ResourceServerId $resourceServerId): AuthCodeCreatedEvent
    {
        return new self($authCodeId, $clientId, $userAccountId, $queryParameters, $redirectUri, $expiresAt, $parameters, $metadatas, $scopes, $withRefreshToken, $resourceServerId, null, null);
    }

    /**
     * @return AuthCodeId
     */
    public function getAuthCodeId(): AuthCodeId
    {
        return $this->authCodeId;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    /**
     * @return UserAccountId
     */
    public function getUserAccountId(): UserAccountId
    {
        return $this->userAccountId;
    }

    /**
     * @return ClientId
     */
    public function getClientId(): ClientId
    {
        return $this->clientId;
    }

    /**
     * @return DataBag
     */
    public function getParameters(): DataBag
    {
        return $this->parameters;
    }

    /**
     * @return DataBag
     */
    public function getMetadatas(): DataBag
    {
        return $this->metadatas;
    }

    /**
     * @return \string[]
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    /**
     * @return array
     */
    public function getQueryParameters(): array
    {
        return $this->queryParameters;
    }

    /**
     * @return string
     */
    public function getRedirectUri(): string
    {
        return $this->redirectUri;
    }

    /**
     * @return ResourceServerId|null
     */
    public function getResourceServerId(): ? ResourceServerId
    {
        return $this->resourceServerId;
    }

    /**
     * @return bool
     */
    public function issueRefreshToken(): bool
    {
        return $this->withRefreshToken;
    }

    /**
     * {@inheritdoc}
     */
    public function getDomainId(): Id
    {
        return $this->getAuthCodeId();
    }

    /**
     * {@inheritdoc}
     */
    public function getPayload()
    {
        return (object) [
            'user_account_id' => $this->userAccountId->getValue(),
            'client_id' => $this->clientId->getValue(),
            'expires_at' => $this->expiresAt->getTimestamp(),
            'parameters' => (object) $this->parameters->all(),
            'metadatas' => (object) $this->metadatas->all(),
            'scopes' => $this->scopes,
            'redirect_uri' => $this->redirectUri,
            'query_parameters' => (object) $this->queryParameters,
            'with_refresh_token' => $this->withRefreshToken,
            'resource_server_id' => $this->resourceServerId ? $this->resourceServerId->getValue() : null,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function createFromJson(\stdClass $json): DomainObjectInterface
    {
        $authCodeId = AuthCodeId::create($json->domain_id);
        $eventId = EventId::create($json->event_id);
        $recordedOn = \DateTimeImmutable::createFromFormat('U', (string) $json->recorded_on);
        $userAccountId = UserAccountId::create($json->payload->user_account_id);
        $clientId = ClientId::create($json->payload->client_id);
        $expiresAt = \DateTimeImmutable::createFromFormat('U', (string) $json->payload->expires_at);
        $parameters = DataBag::createFromArray((array) $json->payload->parameters);
        $metadatas = DataBag::createFromArray((array) $json->payload->metadatas);
        $scopes = (array) $json->payload->scopes;
        $redirectUri = $json->payload->redirect_uri;
        $queryParameters = (array) $json->payload->query_parameters;
        $withRefreshToken = (bool) $json->payload->with_refresh_token;
        $resourceServerId = null !== $json->payload->resource_server_id ? ResourceServerId::create($json->payload->resource_server_id) : null;

        return new self($authCodeId, $clientId, $userAccountId, $queryParameters, $redirectUri, $expiresAt, $parameters, $metadatas, $scopes, $withRefreshToken, $resourceServerId, $recordedOn, $eventId);
    }
}
