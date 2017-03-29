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

namespace OAuth2Framework\Component\Server\Command\ResourceServer;

use OAuth2Framework\Component\Server\Model\ResourceServer\ResourceServerRepositoryInterface;

final class DeleteResourceServerCommandHandler
{
    /**
     * @var ResourceServerRepositoryInterface
     */
    private $resourceServerRepository;

    /**
     * DeleteResourceServerCommandHandler constructor.
     *
     * @param ResourceServerRepositoryInterface $resourceServerRepository
     */
    public function __construct(ResourceServerRepositoryInterface $resourceServerRepository)
    {
        $this->resourceServerRepository = $resourceServerRepository;
    }

    /**
     * @param DeleteResourceServerCommand $command
     */
    public function handle(DeleteResourceServerCommand $command)
    {
        $resourceServerId = $command->getResourceServerId();
        $resourceServer = $this->resourceServerRepository->find($resourceServerId);
        if (null !== $resourceServer) {
            $resourceServer = $resourceServer->markAsDeleted();
            $this->resourceServerRepository->save($resourceServer);
        }
    }
}
