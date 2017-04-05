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

namespace OAuth2Framework\Component\Server\Command\AuthCode;

use OAuth2Framework\Component\Server\Command\CommandWithDataTransporter;
use OAuth2Framework\Component\Server\DataTransporter;
use OAuth2Framework\Component\Server\Model\Client\ClientId;
use OAuth2Framework\Component\Server\Model\DataBag\DataBag;
use OAuth2Framework\Component\Server\Model\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;

final class CreateAuthCodeCommand extends CommandWithDataTransporter
{
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
     * @var \DateTimeImmutable|null
     */
    private $expiresAt;

    /**
     * @var DataBag
     */
    private $parameters;

    /**
     * @var array
     */
    private $scopes;

    /**
     * @var DataBag
     */
    private $metadatas;

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
     * CreateAuthCodeCommand constructor.
     *
     * @param ClientId                $clientId
     * @param UserAccountId           $userAccountId
     * @param array                   $queryParameters
     * @param string                  $redirectUri
     * @param DataBag                 $parameters
     * @param DataBag                 $metadatas
     * @param array                   $scopes
     * @param bool                    $withRefreshToken
     * @param \DateTimeImmutable|null $expiresAt
     * @param ResourceServerId|null   $resourceServerId
     * @param DataTransporter|null    $dataTransporter
     */
    protected function __construct(ClientId $clientId, UserAccountId $userAccountId, array $queryParameters, string $redirectUri, DataBag $parameters, DataBag $metadatas, array $scopes, bool $withRefreshToken, ? \DateTimeImmutable $expiresAt, ? ResourceServerId $resourceServerId, ? DataTransporter $dataTransporter)
    {
        $this->clientId = $clientId;
        $this->userAccountId = $userAccountId;
        $this->queryParameters = $queryParameters;
        $this->expiresAt = $expiresAt;
        $this->redirectUri = $redirectUri;
        $this->parameters = $parameters;
        $this->scopes = $scopes;
        $this->metadatas = $metadatas;
        $this->withRefreshToken = $withRefreshToken;
        $this->resourceServerId = $resourceServerId;
        parent::__construct($dataTransporter);
    }

    /**
     * @param ClientId                $clientId
     * @param UserAccountId           $userAccountId
     * @param array                   $queryParameters
     * @param string                  $redirectUri
     * @param DataBag                 $parameters
     * @param DataBag                 $metadatas
     * @param array                   $scopes
     * @param bool                    $withRefreshToken
     * @param \DateTimeImmutable|null $expiresAt
     * @param ResourceServerId|null   $resourceServerId
     * @param DataTransporter|null    $dataTransporter
     *
     * @return CreateAuthCodeCommand
     */
    public static function create(ClientId $clientId, UserAccountId $userAccountId, array $queryParameters, string $redirectUri, DataBag $parameters, DataBag $metadatas, array $scopes, bool $withRefreshToken, ? \DateTimeImmutable $expiresAt, ? ResourceServerId $resourceServerId, ? DataTransporter $dataTransporter): CreateAuthCodeCommand
    {
        return new self($clientId, $userAccountId, $queryParameters, $redirectUri, $parameters, $metadatas, $scopes, $withRefreshToken, $expiresAt, $resourceServerId, $dataTransporter);
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
     * @return \DateTimeImmutable|null
     */
    public function getExpiresAt(): ? \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    /**
     * @return DataBag
     */
    public function getParameters(): DataBag
    {
        return $this->parameters;
    }

    /**
     * @return array
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    /**
     * @return DataBag
     */
    public function getMetadatas(): DataBag
    {
        return $this->metadatas;
    }

    /**
     * @return bool
     */
    public function hasRefreshToken(): bool
    {
        return $this->withRefreshToken;
    }

    /**
     * @return null|ResourceServerId
     */
    public function getResourcesServerId(): ? ResourceServerId
    {
        return $this->resourceServerId;
    }
}
