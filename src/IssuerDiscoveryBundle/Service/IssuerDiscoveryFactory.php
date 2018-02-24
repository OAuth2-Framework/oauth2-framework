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

namespace OAuth2Framework\IssuerDiscoveryBundle\Service;

use Http\Message\ResponseFactory;
use OAuth2Framework\IssuerDiscoveryBundle\Tests\TestBundle\Entity\ResourceRepository;
use OAuth2Framework\Component\IssuerDiscoveryEndpoint\IdentifierResolver\IdentifierResolverManager;
use OAuth2Framework\Component\IssuerDiscoveryEndpoint\IssuerDiscoveryEndpoint;

class IssuerDiscoveryFactory
{
    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    /**
     * @var IdentifierResolverManager
     */
    private $identifierResolverManager;

    /**
     * IssuerDiscoveryFactory constructor.
     *
     * @param ResponseFactory           $responseFactory
     * @param IdentifierResolverManager $identifierResolverManager
     */
    public function __construct(ResponseFactory $responseFactory, IdentifierResolverManager $identifierResolverManager)
    {
        $this->responseFactory = $responseFactory;
        $this->identifierResolverManager = $identifierResolverManager;
    }

    /**
     * @param ResourceRepository $resourceManager
     * @param string             $server
     * @param int                $port
     *
     * @return IssuerDiscoveryEndpoint
     */
    public function create(ResourceRepository $resourceManager, string $server, int $port): IssuerDiscoveryEndpoint
    {
        return new IssuerDiscoveryEndpoint($resourceManager, $this->responseFactory, $this->identifierResolverManager, $server, $port);
    }
}
