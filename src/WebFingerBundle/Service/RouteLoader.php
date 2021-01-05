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

namespace OAuth2Framework\WebFingerBundle\Service;

use function Safe\sprintf;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RouteLoader implements LoaderInterface
{
    private RouteCollection $routes;

    public function __construct()
    {
        $this->routes = new RouteCollection();
    }

    /**
     * @param string[] $schemes
     * @param string[] $methods
     */
    public function addRoute(string $name, string $controllerId, string $methodName, string $path, array $defaults = [], array $requirements = [], array $options = [], ?string $host = '', array $schemes = [], array $methods = [], string $condition = ''): void
    {
        $defaults['_controller'] = sprintf('%s:%s', $controllerId, $methodName);
        $route = new Route($path, $defaults, $requirements, $options, $host, $schemes, $methods, $condition);
        $this->routes->add(sprintf('webfinger.%s', $name), $route);
    }

    public function load($resource, $type = null): RouteCollection
    {
        return $this->routes;
    }

    public function supports($resource, $type = null): bool
    {
        return 'webfinger' === $type;
    }

    public function getResolver(): ?LoaderResolverInterface
    {
    }

    public function setResolver(LoaderResolverInterface $resolver): void
    {
    }
}
