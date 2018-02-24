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

namespace OAuth2Framework\IssuerDiscoveryBundle\Tests\TestBundle\Entity;

use OAuth2Framework\Component\IssuerDiscoveryEndpoint\ResourceObject as ResourceObjectInterface;
use OAuth2Framework\Component\IssuerDiscoveryEndpoint\ResourceId;
use OAuth2Framework\Component\IssuerDiscoveryEndpoint\ResourceRepository as ResourceRepositoryInterface;

class ResourceRepository implements ResourceRepositoryInterface
{
    /**
     * @var ResourceObjectInterface[]
     */
    private $resources = [];

    public function __construct()
    {
        $this->resources['john'] = new ResourceObject('https://server.example.com');
    }

    /**
     * {@inheritdoc}
     */
    public function find(ResourceId $resourceId): ?ResourceObjectInterface
    {
        return array_key_exists($resourceId->getValue(), $this->resources) ? $this->resources[$resourceId->getValue()] : null;
    }
}
