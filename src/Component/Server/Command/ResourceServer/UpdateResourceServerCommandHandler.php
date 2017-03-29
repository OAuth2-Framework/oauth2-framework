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

final class UpdateResourceServerCommandHandler
{
    /**
     * @var ResourceServerRepositoryInterface
     */
    private $resourceServerRepository;

    /**
     * UpdateResourceServerCommandHandler constructor.
     *
     * @param ResourceServerRepositoryInterface $resourceServerRepository
     */
    public function __construct(ResourceServerRepositoryInterface $resourceServerRepository)
    {
        $this->resourceServerRepository = $resourceServerRepository;
    }

    /**
     * @param UpdateResourceServerCommand $command
     */
    public function handle(UpdateResourceServerCommand $command)
    {
        $parameters = $command->getParameters();
        $resourceServer = $command->getResourceServer();
        $resourceServer = $resourceServer->withParameters($parameters);
        $this->resourceServerRepository->save($resourceServer);
        if (null !== $command->getDataTransporter()) {
            $callback = $command->getDataTransporter();
            $callback($resourceServer);
        }
    }
}
