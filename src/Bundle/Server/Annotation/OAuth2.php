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

namespace OAuth2Framework\Bundle\Server\Annotation;

/**
 * Annotation class for @OAuth2().
 *
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
final class OAuth2
{
    /**
     * @var null|string
     */
    private $scope = null;

    /**
     * @var null|string
     */
    private $clientId = null;

    /**
     * @var null|string
     */
    private $resourceOwnerId = null;

    /**
     * @param array $data an array of key/value parameters
     *
     * @throws \BadMethodCallException
     */
    public function __construct(array $data)
    {
        if (isset($data['value'])) {
            $data['path'] = $data['value'];
            unset($data['value']);
        }

        foreach ($data as $key => $value) {
            $method = 'set'.str_replace('_', '', ucwords($key, '_'));
            if (!method_exists($this, $method)) {
                throw new \BadMethodCallException(sprintf("Unknown property '%s' on annotation '%s'.", $key, get_class($this)));
            }
            $this->$method($value);
        }
    }

    /**
     * @param string $clientId
     */
    protected function setClientId(string $clientId)
    {
        Assertion::string($clientId, 'The client public ID should be a string.');
        $this->clientId = $clientId;
    }

    /**
     * @return null|string
     */
    public function getClientId(): ?string
    {
        return $this->clientId;
    }

    /**
     * @param string $resourceOwnerId
     */
    protected function setResourceOwnerId(string $resourceOwnerId)
    {
        Assertion::string($resourceOwnerId, 'The resource owner public ID should be a string.');
        $this->resourceOwnerId = $resourceOwnerId;
    }

    /**
     * @return null|string
     */
    public function getResourceOwnerId(): ?string
    {
        return $this->resourceOwnerId;
    }

    /**
     * @param string $scope
     */
    protected function setScope(string $scope)
    {
        $this->scope = $scope;
    }

    /**
     * @return null|string
     */
    public function getScope(): ?string
    {
        return $this->scope;
    }
}
