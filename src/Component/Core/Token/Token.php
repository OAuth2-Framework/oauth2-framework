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

namespace OAuth2Framework\Component\Core\Token;

use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\Domain\DomainObject;
use SimpleBus\Message\Recorder\ContainsRecordedMessages;
use SimpleBus\Message\Recorder\PrivateMessageRecorderCapabilities;

abstract class Token implements \JsonSerializable, ContainsRecordedMessages, DomainObject
{
    use PrivateMessageRecorderCapabilities;

    /**
     * @var \DateTimeImmutable|null
     */
    protected $expiresAt = null;

    /**
     * @var ResourceOwnerId|null
     */
    protected $resourceOwnerId = null;

    /**
     * @var ClientId|null
     */
    protected $clientId = null;

    /**
     * @var DataBag
     */
    protected $parameters;

    /**
     * @var DataBag
     */
    protected $metadatas;

    /**
     * @var bool
     */
    protected $revoked = false;

    /**
     * @var null|ResourceServerId
     */
    protected $resourceServerId = null;

    /**
     * Token constructor.
     */
    protected function __construct()
    {
        $this->parameters = DataBag::create([]);
        $this->metadatas = DataBag::create([]);
    }

    /**
     * @return TokenId
     */
    abstract public function getTokenId(): TokenId;

    /**
     * @return ResourceOwnerId
     */
    public function getResourceOwnerId(): ResourceOwnerId
    {
        return $this->resourceOwnerId;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    /**
     * @return bool
     */
    public function hasExpired(): bool
    {
        return $this->expiresAt->getTimestamp() < time();
    }

    /**
     * {@inheritdoc}
     */
    public function getExpiresIn(): int
    {
        $expiresAt = $this->expiresAt;
        if (null === $expiresAt) {
            return 0;
        }

        return $this->expiresAt->getTimestamp() - time() < 0 ? 0 : $this->expiresAt->getTimestamp() - time();
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
        return $this->parameters->has($key);
    }

    /**
     * @return bool
     */
    public function isRevoked(): bool
    {
        return $this->revoked;
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
        if (!$this->hasParameter($key)) {
            throw new \InvalidArgumentException(sprintf('The parameter "%s" does not exist.', $key));
        }

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
        return $this->metadatas->has($key);
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
        if (!$this->hasMetadata($key)) {
            throw new \InvalidArgumentException(sprintf('The metadata "%s" does not exist.', $key));
        }

        return $this->metadatas->get($key);
    }

    /**
     * @return null|ResourceServerId
     */
    public function getResourceServerId(): ? ResourceServerId
    {
        return $this->resourceServerId;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $data = [
            '$schema' => $this->getSchema(),
            'type' => get_class($this),
            'expires_at' => $this->getExpiresAt()->getTimestamp(),
            'client_id' => $this->getClientId()->getValue(),
            'parameters' => (object) $this->getParameters()->all(),
            'metadatas' => (object) $this->getMetadatas()->all(),
            'is_revoked' => $this->isRevoked(),
            'resource_owner_id' => $this->getResourceOwnerId()->getValue(),
            'resource_owner_class' => get_class($this->getResourceOwnerId()),
            'resource_server_id' => $this->getResourceServerId() ? $this->getResourceServerId()->getValue() : null,
        ];

        return $data;
    }
}
