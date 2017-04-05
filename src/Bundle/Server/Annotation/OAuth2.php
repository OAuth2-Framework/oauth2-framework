<?php

declare(strict_types = 1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Bundle\Server\Annotation;

use Assert\Assertion;

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
    private $clientType = null;

    /**
     * @var null|string
     */
    private $clientPublicId = null;

    /**
     * @var null|string
     */
    private $resourceOwnerType = null;

    /**
     * @var null|string
     */
    private $resourceOwnerPublicId = null;

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
            $method = 'set'.str_replace('_', '', $key);
            if (!method_exists($this, $method)) {
                throw new \BadMethodCallException(sprintf("Unknown property '%s' on annotation '%s'.", $key, get_class($this)));
            }
            $this->$method($value);
        }
    }

    /**
     * @param string $clientType
     */
    public function setClientType(string $clientType)
    {
        Assertion::inArray($clientType, ['public', 'confidential'], sprintf('Invalid client type. Please use one of the following value: ', json_encode(['public', 'confidential'])));
        $this->clientType = $clientType;
    }

    /**
     * @return null|string
     */
    public function getClientType()
    {
        return $this->clientType;
    }

    /**
     * @param string $clientPublicId
     */
    public function setClientPublicId(string $clientPublicId)
    {
        Assertion::string($clientPublicId, 'The client public ID should be a string.');
        $this->clientPublicId = $clientPublicId;
    }

    /**
     * @return null|string
     */
    public function getClientPublicId()
    {
        return $this->clientPublicId;
    }

    /**
     * @param string $resourceOwnerType
     */
    public function setResourceOwnerType(string $resourceOwnerType)
    {
        Assertion::inArray($resourceOwnerType, ['end_user', 'client'], sprintf('Invalid resource owner type. Please use one of the following value: ', json_encode(['end_user', 'client'])));
        $this->resourceOwnerType = $resourceOwnerType;
    }

    /**
     * @return null|string
     */
    public function getResourceOwnerType()
    {
        return $this->resourceOwnerType;
    }

    /**
     * @param string $resourceOwnerPublicId
     */
    public function setResourceOwnerPublicId(string $resourceOwnerPublicId)
    {
        Assertion::string($resourceOwnerPublicId, 'The resource owner public ID should be a string.');
        $this->resourceOwnerPublicId = $resourceOwnerPublicId;
    }

    /**
     * @return null|string
     */
    public function getResourceOwnerPublicId()
    {
        return $this->resourceOwnerPublicId;
    }

    /**
     * @param string $scope
     */
    public function setScope(string $scope)
    {
        $this->scope = $scope;
    }

    /**
     * @return null|string
     */
    public function getScope()
    {
        return $this->scope;
    }
}
