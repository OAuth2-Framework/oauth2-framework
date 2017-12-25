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

namespace OAuth2Framework\Component\Server\AuthorizationCodeGrant\Command;

use OAuth2Framework\Component\Server\AuthorizationCodeGrant\AuthorizationCodeId;
use OAuth2Framework\Component\Server\Core\Client\ClientId;
use OAuth2Framework\Component\Server\Core\DataBag\DataBag;
use OAuth2Framework\Component\Server\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Server\Core\UserAccount\UserAccountId;

final class CreateAuthorizationCodeCommand
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
     * @var ClientId
     */
    private $clientId;

    /**
     * @var UserAccountId
     */
    private $userAccountId;

    /**
     * @var array
     */
    private $queryParameters;

    /**
     * @var string
     */
    private $redirectUri;

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
    private $scopes;

    /**
     * @var null|ResourceServerId
     */
    private $resourceServerId;

    /**
     * CreateAuthorizationCodeCommand constructor.
     *
     * @param AuthorizationCodeId   $authorizationCodeId
     * @param ClientId              $clientId
     * @param UserAccountId         $userAccountId
     * @param array                 $queryParameters
     * @param string                $redirectUri
     * @param \DateTimeImmutable    $expiresAt
     * @param DataBag               $parameters
     * @param DataBag               $metadatas
     * @param array                 $scopes
     * @param null|ResourceServerId $resourceServerId
     */
    protected function __construct(AuthorizationCodeId $authorizationCodeId, ClientId $clientId, UserAccountId $userAccountId, array $queryParameters, string $redirectUri, \DateTimeImmutable $expiresAt, DataBag $parameters, DataBag $metadatas, array $scopes, ?ResourceServerId $resourceServerId)
    {
        $this->authorizationCodeId = $authorizationCodeId;
        $this->clientId = $clientId;
        $this->userAccountId = $userAccountId;
        $this->queryParameters = $queryParameters;
        $this->redirectUri = $redirectUri;
        $this->expiresAt = $expiresAt;
        $this->parameters = $parameters;
        $this->metadatas = $metadatas;
        $this->scopes = $scopes;
        $this->resourceServerId = $resourceServerId;
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
     * @param array                 $scopes
     * @param null|ResourceServerId $resourceServerId
     *
     * @return CreateAuthorizationCodeCommand
     */
    public static function create(AuthorizationCodeId $authorizationCodeId, ClientId $clientId, UserAccountId $userAccountId, array $queryParameters, string $redirectUri, \DateTimeImmutable $expiresAt, DataBag $parameters, DataBag $metadatas, array $scopes, ?ResourceServerId $resourceServerId): self
    {
        return new self($authorizationCodeId, $clientId, $userAccountId, $queryParameters, $redirectUri, $expiresAt, $parameters, $metadatas, $scopes, $resourceServerId);
    }

    /**
     * @return ClientId
     */
    public function getClientId(): ClientId
    {
        return $this->clientId;
    }

    /**
     * @return UserAccountId
     */
    public function getUserAccountId(): UserAccountId
    {
        return $this->userAccountId;
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
    public function getScopes(): array
    {
        return $this->scopes;
    }

    /**
     * @return null|ResourceServerId
     */
    public function getResourceServerId(): ?ResourceServerId
    {
        return $this->resourceServerId;
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
}
