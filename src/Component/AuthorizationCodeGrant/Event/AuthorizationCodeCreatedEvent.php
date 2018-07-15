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

namespace OAuth2Framework\Component\AuthorizationCodeGrant\Event;

use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeId;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\Domain\DomainObject;
use OAuth2Framework\Component\Core\Event\Event;
use OAuth2Framework\Component\Core\Id\Id;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;

class AuthorizationCodeCreatedEvent extends Event
{
    /**
     * @var AuthorizationCodeId
     */
    private $authorizationCodeId;

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
     * @var array
     */
    private $queryParameters;

    /**
     * @var string
     */
    private $redirectUri;

    /**
     * @var ResourceServerId|null
     */
    private $resourceServerId;

    /**
     * AuthorizationCodeCreatedEvent constructor.
     *
     * @param AuthorizationCodeId   $authorizationCodeId
     * @param ClientId              $clientId
     * @param UserAccountId         $userAccountId
     * @param array                 $queryParameters
     * @param string                $redirectUri
     * @param \DateTimeImmutable    $expiresAt
     * @param DataBag               $parameters
     * @param DataBag               $metadatas
     * @param ResourceServerId|null $resourceServerId
     */
    protected function __construct(AuthorizationCodeId $authorizationCodeId, ClientId $clientId, UserAccountId $userAccountId, array $queryParameters, string $redirectUri, \DateTimeImmutable $expiresAt, DataBag $parameters, DataBag $metadatas, ?ResourceServerId $resourceServerId)
    {
        $this->authorizationCodeId = $authorizationCodeId;
        $this->userAccountId = $userAccountId;
        $this->clientId = $clientId;
        $this->expiresAt = $expiresAt;
        $this->parameters = $parameters;
        $this->metadatas = $metadatas;
        $this->redirectUri = $redirectUri;
        $this->queryParameters = $queryParameters;
        $this->resourceServerId = $resourceServerId;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSchema(): string
    {
        return 'https://oauth2-framework.spomky-labs.com/schemas/events/authorization-code/created/1.0/schema';
    }

    /**
     * @param AuthorizationCodeId   $authorizationCodeId
     * @param ClientId              $clientId
     * @param UserAccountId         $userAccountId
     * @param array                 $queryParameters
     * @param string                $redirectUri
     * @param \DateTimeImmutable    $expiresAt
     * @param DataBag               $parameters
     * @param DataBag               $metadatas
     * @param ResourceServerId|null $resourceServerId
     *
     * @return AuthorizationCodeCreatedEvent
     */
    public static function create(AuthorizationCodeId $authorizationCodeId, ClientId $clientId, UserAccountId $userAccountId, array $queryParameters, string $redirectUri, \DateTimeImmutable $expiresAt, DataBag $parameters, DataBag $metadatas, ?ResourceServerId $resourceServerId): self
    {
        return new self($authorizationCodeId, $clientId, $userAccountId, $queryParameters, $redirectUri, $expiresAt, $parameters, $metadatas, $resourceServerId);
    }

    /**
     * @return AuthorizationCodeId
     */
    public function getAuthorizationCodeId(): AuthorizationCodeId
    {
        return $this->authorizationCodeId;
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
    public function getResourceServerId(): ?ResourceServerId
    {
        return $this->resourceServerId;
    }

    /**
     * {@inheritdoc}
     */
    public function getDomainId(): Id
    {
        return $this->getAuthorizationCodeId();
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
            'redirect_uri' => $this->redirectUri,
            'query_parameters' => (object) $this->queryParameters,
            'resource_server_id' => $this->resourceServerId ? $this->resourceServerId->getValue() : null,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function createFromJson(\stdClass $json): DomainObject
    {
        $authorizationCodeId = AuthorizationCodeId::create($json->domain_id);
        $userAccountId = UserAccountId::create($json->payload->user_account_id);
        $clientId = ClientId::create($json->payload->client_id);
        $expiresAt = \DateTimeImmutable::createFromFormat('U', (string) $json->payload->expires_at);
        $parameters = DataBag::create((array) $json->payload->parameters);
        $metadatas = DataBag::create((array) $json->payload->metadatas);
        $redirectUri = $json->payload->redirect_uri;
        $queryParameters = (array) $json->payload->query_parameters;
        $resourceServerId = null !== $json->payload->resource_server_id ? ResourceServerId::create($json->payload->resource_server_id) : null;

        return new self($authorizationCodeId, $clientId, $userAccountId, $queryParameters, $redirectUri, $expiresAt, $parameters, $metadatas, $resourceServerId);
    }
}
