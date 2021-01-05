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
    private ?string $scope;

    private ?string $token_type;

    private ?string $client_id;

    private ?string $resource_owner_id;

    private ?array $custom;

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

    public function getCustom(): ?array
    {
        return $this->custom;
    }
}
