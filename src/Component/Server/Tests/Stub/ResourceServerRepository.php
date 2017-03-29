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

namespace OAuth2Framework\Component\Server\Tests\Stub;

use OAuth2Framework\Component\Server\Model\DataBag\DataBag;
use OAuth2Framework\Component\Server\Model\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Server\Model\ResourceServer\ResourceServerInterface;
use OAuth2Framework\Component\Server\Model\ResourceServer\ResourceServerRepositoryInterface;

final class ResourceServerRepository implements ResourceServerRepositoryInterface
{
    /**
     * @var ResourceServerInterface[]
     */
    private $resourceServers = [];

    /**
     * ResourceServerRepository constructor.
     */
    public function __construct()
    {
        $this->createAndSaveResourceServer(
            ResourceServerId::create('ResourceServer1'),
            DataBag::createFromArray([
                'ips' => ['127.0.0.1', '192.168.0.1'],
            ])
        );
        $this->createAndSaveResourceServer(
            ResourceServerId::create('ResourceServer2'),
            DataBag::createFromArray([
                'ips' => ['127.0.0.1', '192.168.0.1'],
            ])
        );
    }

    /**
     * {@inheritdoc}
     */
    public function find(ResourceServerId $resourceServerId): ?ResourceServerInterface
    {
        if (array_key_exists($resourceServerId->getValue(), $this->resourceServers)) {
            return $this->resourceServers[$resourceServerId->getValue()];
        }

        return null;
    }

    private function createAndSaveResourceServer(ResourceServerId $resourceServerId, DataBag $parameters, $markAsDeleted = false)
    {
        $resourceServer = ResourceServer::create($resourceServerId, $parameters, $markAsDeleted);
        $this->resourceServers[$resourceServer->getResourceServerId()->getValue()] = $resourceServer;
    }
}
