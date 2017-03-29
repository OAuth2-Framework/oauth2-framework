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

use Assert\Assertion;
use OAuth2Framework\Component\Server\Model\DataBag\DataBag;
use OAuth2Framework\Component\Server\Model\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Server\Model\ResourceServer\ResourceServerInterface;

/**
 * Class ResourceServer.
 */
final class ResourceServer implements ResourceServerInterface
{
    /**
     * @var ResourceServerId
     */
    private $resourceServerId;

    /**
     * @var DataBag
     */
    private $parameters;

    /**
     * @var bool
     */
    private $deleted;

    /**
     * ResourceServer constructor.
     *
     * @param ResourceServerId $resourceServerId
     * @param DataBag          $parameters
     */
    private function __construct(ResourceServerId $resourceServerId, DataBag $parameters, bool $deleted)
    {
        $this->resourceServerId = $resourceServerId;
        $this->parameters = $parameters;
        $this->deleted = $deleted;
    }

    /**
     * @param ResourceServerId $resourceServerId
     * @param DataBag          $parameters
     * @param bool             $deleted
     *
     * @return ResourceServer
     */
    public static function create(ResourceServerId $resourceServerId, DataBag $parameters, bool $deleted): ResourceServer
    {
        return new self($resourceServerId, $parameters, $deleted);
    }

    /**
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceServerId(): ResourceServerId
    {
        Assertion::notNull($this->resourceServerId, 'Resource Server not initialized.');

        return $this->resourceServerId;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->parameters->has($key);
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function get(string $key)
    {
        Assertion::true($this->has($key), sprintf('Parameter key \'%s\' does not exist.', $key));

        return $this->parameters->get($key);
    }
}
