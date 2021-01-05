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

namespace OAuth2Framework\WebFingerBundle\Tests\TestBundle\Entity;

use OAuth2Framework\Component\WebFingerEndpoint\IdentifierResolver\Identifier;
use OAuth2Framework\Component\WebFingerEndpoint\Link;
use OAuth2Framework\Component\WebFingerEndpoint\ResourceDescriptor;
use OAuth2Framework\Component\WebFingerEndpoint\ResourceRepository as ResourceRepositoryInterface;

final class ResourceRepository implements ResourceRepositoryInterface
{
    /**
     * @var ResourceDescriptor[]
     */
    private $resources = [];

    public function __construct()
    {
        $this->resources['john'] = new ResourceDescriptor(
            'acct:john@my-service.com:443',
            [
                'https://my-service.com:443/+john',
            ],
            [],
            [
                new Link('http://openid.net/specs/connect/1.0/issuer', null, 'https://server.example.com', [], []),
            ]
        );
    }

    public function find(string $resource, Identifier $identifier): ?ResourceDescriptor
    {
        $resourceDescriptor = $this->resources[$identifier->getId()] ?? null;
        if (null === $resourceDescriptor) {
            return null;
        }

        if ($resource !== $resourceDescriptor->getSubject() && !\in_array($resource, $resourceDescriptor->getAliases(), true)) {
            return null;
        }

        return $resourceDescriptor;
    }
}
