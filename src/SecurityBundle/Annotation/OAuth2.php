<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\SecurityBundle\Annotation;

use BadMethodCallException;
use function Safe\sprintf;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class OAuth2
{
    /**
     * @var null|string
     */
    private $scope;

    /**
     * @var null|string
     */
    private $token_type;

    /**
     * @var null|string
     */
    private $client_id;

    /**
     * @var null|string
     */
    private $resource_owner_id;

    public function __construct(array $data)
    {
        foreach ($data as $key => $value) {
            if (!property_exists($this, $key)) {
                throw new BadMethodCallException(sprintf('Unknown property "%s" on annotation "%s".', $key, \get_class($this)));
            }
            $this->{$key} = $value;
        }
    }

    public function getClientId(): ?string
    {
        return $this->client_id;
    }

    public function getResourceOwnerId(): ?string
    {
        return $this->resource_owner_id;
    }

    public function getScope(): ?string
    {
        return $this->scope;
    }

    public function getTokenType(): ?string
    {
        return $this->token_type;
    }
}
