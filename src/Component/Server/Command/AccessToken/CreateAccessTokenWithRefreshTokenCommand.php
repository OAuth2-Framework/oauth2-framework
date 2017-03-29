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

namespace OAuth2Framework\Component\Server\Command\AccessToken;

use Assert\Assertion;
use OAuth2Framework\Component\Server\Command\CommandWithDataTransporter;
use OAuth2Framework\Component\Server\DataTransporter;
use OAuth2Framework\Component\Server\Model\Client\ClientId;
use OAuth2Framework\Component\Server\Model\DataBag\DataBag;
use OAuth2Framework\Component\Server\Model\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Server\Model\ResourceServer\ResourceServerId;

final class CreateAccessTokenWithRefreshTokenCommand extends CommandWithDataTransporter
{
    /**
     * @var ResourceOwnerId
     */
    private $resourceOwnerId;

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
     * @var \DateTimeImmutable|null
     */
    private $expiresAt;

    /**
     * @var ResourceServerId|null
     */
    private $resourceServerId;

    /**
     * CreateAccessTokenCommand constructor.
     *
     * @param ResourceOwnerId         $resourceOwnerId
     * @param ClientId                $clientId
     * @param DataBag                 $parameters
     * @param DataBag                 $metadatas
     * @param array                   $scopes
     * @param \DateTimeImmutable|null $expiresAt
     * @param ResourceServerId|null   $resourceServerId
     * @param DataTransporter|null    $dataTransporter
     */
    protected function __construct(ClientId $clientId, ResourceOwnerId $resourceOwnerId, DataBag $parameters, DataBag $metadatas, array $scopes, ?\DateTimeImmutable $expiresAt, ?ResourceServerId $resourceServerId, ?DataTransporter $dataTransporter)
    {
        $this->resourceOwnerId = $resourceOwnerId;
        $this->clientId = $clientId;
        $this->parameters = $parameters;
        $this->metadatas = $metadatas;
        $this->scopes = $scopes;
        $this->expiresAt = $expiresAt;
        $this->resourceServerId = $resourceServerId;
        parent::__construct($dataTransporter);
    }

    /**
     * @param ResourceOwnerId         $resourceOwnerId
     * @param ClientId                $clientId
     * @param DataBag                 $parameters
     * @param DataBag                 $metadatas
     * @param array                   $scopes
     * @param \DateTimeImmutable|null $expiresAt
     * @param ResourceServerId|null   $resourceServerId
     * @param DataTransporter|null    $dataTransporter
     *
     * @return CreateAccessTokenWithRefreshTokenCommand
     */
    public static function create(ClientId $clientId, ResourceOwnerId $resourceOwnerId, DataBag $parameters, DataBag $metadatas, array $scopes, ?\DateTimeImmutable $expiresAt, ?ResourceServerId $resourceServerId, ?DataTransporter $dataTransporter): CreateAccessTokenWithRefreshTokenCommand
    {
        return new self($clientId, $resourceOwnerId, $parameters, $metadatas, $scopes, $expiresAt, $resourceServerId, $dataTransporter);
    }

    /**
     * @return ResourceOwnerId
     */
    public function getResourceOwnerId(): ResourceOwnerId
    {
        return $this->resourceOwnerId;
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
     * @param string $key
     *
     * @return bool
     */
    public function hasParameter(string $key): bool
    {
        return $this->parameters->get($key);
    }

    /**
     * @param string $key
     *
     * @throws \InvalidArgumentException
     *
     * @return mixed
     */
    public function getParameter(string $key)
    {
        Assertion::true($this->hasParameter($key), sprintf('The parameter \'%s\' does not exist.', $key));

        return $this->parameters->get($key);
    }

    /**
     * @return DataBag
     */
    public function getMetadatas(): DataBag
    {
        return $this->metadatas;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function hasMetadata(string $key): bool
    {
        return $this->metadatas->get($key);
    }

    /**
     * @param string $key
     *
     * @throws \InvalidArgumentException
     *
     * @return mixed
     */
    public function getMetadata(string $key)
    {
        Assertion::true($this->hasParameter($key), sprintf('The metadata \'%s\' does not exist.', $key));

        return $this->metadatas->get($key);
    }

    /**
     * @return string[]
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    /**
     * @return \DateTimeImmutable|null
     */
    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    /**
     * @return ResourceServerId|null
     */
    public function getResourceServerId(): ?ResourceServerId
    {
        return $this->resourceServerId;
    }
}
