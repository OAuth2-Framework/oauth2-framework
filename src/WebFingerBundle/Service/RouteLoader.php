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

namespace OAuth2Framework\WebFingerBundle\Service;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RouteLoader implements LoaderInterface
{
    private $routes;

    public function __construct()
    {
        $this->routes = new RouteCollection();
    }

    public function addRoute(string $name, string $controllerId, string $methodName, string $path, array $defaults = [], array $requirements = [], array $options = [], ?string $host = '', array $schemes = [], array $methods = [], string $condition = ''): void
    {
        $defaults['_controller'] = \Safe\sprintf('%s:%s', $controllerId, $methodName);
        $route = new Route($path, $defaults, $requirements, $options, $host, $schemes, $methods, $condition);
        $this->routes->add(\Safe\sprintf('webfinger.%s', $name), $route);
    }

    public function load($resource, $type = null)
    {
        return $this->routes;
    }

    public function supports($resource, $type = null)
    {
        return 'webfinger' === $type;
    }

    public function getResolver()
    {
    }

    public function setResolver(LoaderResolverInterface $resolver)
    {
    }
}
