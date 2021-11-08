<?php

declare(strict_types=1);

namespace OAuth2Framework\WebFingerBundle\Service;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RouteLoader implements LoaderInterface
{
    private RouteCollection $routes;

    private LoaderResolverInterface $resolver;

    public function __construct()
    {
        $this->routes = new RouteCollection();
    }

    /**
     * @param string[] $schemes
     * @param string[] $methods
     */
    public function addRoute(
        string $name,
        string $controllerId,
        string $methodName,
        string $path,
        array $defaults = [],
        array $requirements = [],
        array $options = [],
        ?string $host = '',
        array $schemes = [],
        array $methods = [],
        string $condition = ''
    ): void {
        $defaults['_controller'] = sprintf('%s::%s', $controllerId, $methodName);
        $route = new Route($path, $defaults, $requirements, $options, $host, $schemes, $methods, $condition);
        $this->routes->add(sprintf('webfinger.%s', $name), $route);
    }

    public function load(mixed $resource, ?string $type = null): RouteCollection
    {
        return $this->routes;
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        return $type === 'webfinger';
    }

    public function getResolver(): LoaderResolverInterface
    {
        return $this->resolver;
    }

    public function setResolver(LoaderResolverInterface $resolver): void
    {
        $this->resolver = $resolver;
    }
}
