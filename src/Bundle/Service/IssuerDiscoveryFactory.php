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

namespace OAuth2Framework\Bundle\Service;

use Http\Message\ResponseFactory;
use OAuth2Framework\Bundle\Tests\TestBundle\Entity\ResourceRepository;
use OAuth2Framework\Component\IssuerDiscoveryEndpoint\IssuerDiscoveryEndpoint;

class IssuerDiscoveryFactory
{
    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    /**
     * IssuerDiscoveryFactory constructor.
     *
     * @param ResponseFactory $responseFactory
     */
    public function __construct(ResponseFactory $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    /**
     * @param ResourceRepository $resourceManager
     * @param string             $server
     *
     * @return IssuerDiscoveryEndpoint
     */
    public function create(ResourceRepository $resourceManager, string $server): IssuerDiscoveryEndpoint
    {
        return new IssuerDiscoveryEndpoint($resourceManager, $this->responseFactory, $server);
    }
}
