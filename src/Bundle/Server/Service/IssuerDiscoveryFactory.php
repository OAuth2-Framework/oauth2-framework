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

namespace OAuth2Framework\Bundle\Server\Service;

use Interop\Http\Factory\ResponseFactoryInterface;
use Interop\Http\Factory\UriFactoryInterface;
use OAuth2Framework\Component\Server\Endpoint\IssuerDiscovery\IssuerDiscoveryEndpoint;
use OAuth2Framework\Component\Server\Model\Resource\ResourceRepositoryInterface;

final class IssuerDiscoveryFactory
{
    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * @var UriFactoryInterface
     */
    private $uriFactory;

    /**
     * IssuerDiscoveryFactory constructor.
     *
     * @param ResponseFactoryInterface $responseFactory
     * @param UriFactoryInterface      $uriFactory
     */
    public function __construct(ResponseFactoryInterface $responseFactory, UriFactoryInterface $uriFactory)
    {
        $this->responseFactory = $responseFactory;
        $this->uriFactory = $uriFactory;
    }

    /**
     * @param ResourceRepositoryInterface $resourceManager
     * @param string                      $server
     *
     * @return IssuerDiscoveryEndpoint
     */
    public function create(ResourceRepositoryInterface $resourceManager, string $server): IssuerDiscoveryEndpoint
    {
        return new IssuerDiscoveryEndpoint($resourceManager, $this->responseFactory, $this->uriFactory, $server);
    }
}
