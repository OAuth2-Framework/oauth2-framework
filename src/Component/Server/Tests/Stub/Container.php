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

final class Container implements ContainerInterface
{
    /**
     * @var array
     */
    private $services = [];

    /**
     * @param $service
     *
     * @return ContainerInterface
     */
    public function add($service)
    {
        $class = get_class($service);
        $this->services[$class] = $service;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get($id)
    {
        if ($this->has($id)) {
            return $this->services[$id];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function has($id)
    {
        return array_key_exists($id, $this->services);
    }
}
