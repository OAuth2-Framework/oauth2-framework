<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Routing;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RouteLoader implements LoaderInterface
{
    private readonly RouteCollection $routes;

    private LoaderResolverInterface $resolver;

    public function __construct()
    {
        $this->routes = new RouteCollection();
    }

    /**
     * @param array    $defaults     An array of default parameter values
     * @param array    $requirements An array of requirements for parameters (regexes)
     * @param array    $options      An array of options
     * @param string[] $schemes      A required URI scheme or an array of restricted schemes
     * @param string[] $methods      A required HTTP method or an array of restricted methods
     */
    public function addRoute(
        string $name,
        string $controllerId,
        string $methodName,
        string $path,
        array $defaults = [],
        array $requirements = [],
        array $options = [],
        string $host = '',
        array $schemes = [],
        array $methods = [],
        string $condition = ''
    ): void {
        $defaults['_controller'] = sprintf('%s::%s', $controllerId, $methodName);
        $route = new Route($path, $defaults, $requirements, $options, $host, $schemes, $methods, $condition);
        $this->routes->add(sprintf('oauth2_server_%s', $name), $route);
    }

    public function load(mixed $resource, ?string $type = null): RouteCollection
    {
        return $this->routes;
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        return $type === 'oauth2_server';
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
