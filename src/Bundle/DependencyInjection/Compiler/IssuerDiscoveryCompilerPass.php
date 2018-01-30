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

namespace OAuth2Framework\Bundle\DependencyInjection\Compiler;

use OAuth2Framework\Bundle\Routing\RouteLoader;
use OAuth2Framework\Bundle\Service\IssuerDiscoveryFactory;
use OAuth2Framework\Component\Endpoint\IssuerDiscovery\IssuerDiscoveryEndpoint;
use OAuth2Framework\Component\Middleware\OAuth2ResponseMiddleware;
use OAuth2Framework\Component\Middleware\Pipe;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class IssuerDiscoveryCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(IssuerDiscoveryFactory::class)) {
            return;
        }

        $issuerDiscoveries = $container->getParameter('oauth2_server.endpoint.issuer_discovery');

        foreach ($issuerDiscoveries as $id => $issuerDiscovery) {
            $issuerDiscovery_id = sprintf('oauth2_server_issuer_discovery_%s', $id);
            $issuerDiscoveryDefinition = $container->register($issuerDiscovery_id);
            $issuerDiscoveryDefinition->setFactory([new Reference(IssuerDiscoveryFactory::class), 'create']);
            $issuerDiscoveryDefinition->setClass(IssuerDiscoveryEndpoint::class);
            $issuerDiscoveryDefinition->setArguments([
                new Reference($issuerDiscovery['resource_repository']),
                $issuerDiscovery['server'],
            ]);

            $issuerDiscoveryPipeId = sprintf('oauth2_server_issuer_discovery_pipe_%s', $id);
            $issuerDiscoveryPipeDefinition = $container->register($issuerDiscoveryPipeId);
            $issuerDiscoveryPipeDefinition->setClass(Pipe::class);
            $issuerDiscoveryPipeDefinition->setArguments([[
                new Reference(OAuth2ResponseMiddleware::class),
                new Reference($issuerDiscovery_id),
            ]]);
            $route_loader = $container->getDefinition(RouteLoader::class);
            $route_loader->addMethodCall('addRoute', [
                $id,
                $issuerDiscoveryPipeId,
                'dispatch',
                $issuerDiscovery['path'],
                [], // defaults
                [], // requirements
                [], // options
                '', // host
                ['https'], // schemes
                ['GET'], // methods
                '', // condition
            ]);
        }
    }
}
