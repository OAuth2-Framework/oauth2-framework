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

namespace OAuth2Framework\ServerBundle\Annotation;

/**
 * Annotation class for @OAuth2().
 *
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class OAuth2
{
    /**
     * @var null|string
     */
    private $scope = null;

    /**
     * @var null|string
     */
    private $token_type = null;

    /**
     * @var null|string
     */
    private $client_id = null;

    /**
     * @var null|string
     */
    private $resource_owner_id = null;

    /**
     * @param array $data an array of key/value parameters
     *
     * @throws \BadMethodCallException
     */
    public function __construct(array $data)
    {
        foreach ($data as $key => $value) {
            if (!property_exists($this, $key)) {
                throw new \BadMethodCallException(sprintf('Unknown property "%s" on annotation "%s".', $key, get_class($this)));
            }
            $this->$key = $value;
        }
    }

    /**
     * @return null|string
     */
    public function getClientId(): ?string
    {
        return $this->client_id;
    }

    /**
     * @return null|string
     */
    public function getResourceOwnerId(): ?string
    {
        return $this->resource_owner_id;
    }

    /**
     * @return null|string
     */
    public function getScope(): ?string
    {
        return $this->scope;
    }

    /**
     * @return null|string
     */
    public function getTokenType(): ?string
    {
        return $this->token_type;
    }
}
