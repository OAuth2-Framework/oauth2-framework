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

use Psr\Container\ContainerInterface;

final class ServiceLocator
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * ServiceLocator constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $service
     *
     * @return null|callable
     */
    public function __invoke(string $service)
    {
        if ($this->container->has($service)) {
            return $this->container->get($service);
        }
    }
}
