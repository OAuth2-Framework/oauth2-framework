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

namespace OAuth2Framework\IssuerDiscoveryBundle\DependencyInjection\Compiler;

use OAuth2Framework\Component\Middleware\Pipe;
use OAuth2Framework\IssuerDiscoveryBundle\Service\IssuerDiscoveryFactory;
use OAuth2Framework\IssuerDiscoveryBundle\Service\RouteLoader;
use OAuth2Framework\Component\IssuerDiscoveryEndpoint\IssuerDiscoveryEndpoint;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class IssuerDiscoveryCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(IssuerDiscoveryFactory::class)) {
            return;
        }

        $issuerDiscoveries = $container->getParameter('issuer_discovery.endpoints');
        $port = $container->getParameter('request_listener.https_port');

        foreach ($issuerDiscoveries as $id => $issuerDiscovery) {
            $issuerDiscoveryId = sprintf('issuer_discovery.%s', $id);
            $issuerDiscoveryDefinition = (new Definition())
                ->setFactory([new Reference(IssuerDiscoveryFactory::class), 'create'])
                ->setClass(IssuerDiscoveryEndpoint::class)
                ->setArguments([
                    new Reference($issuerDiscovery['resource_repository']),
                    $issuerDiscovery['host'],
                    $port,
                ]);
            $container->setDefinition($issuerDiscoveryId, $issuerDiscoveryDefinition);

            $issuerDiscoveryPipeId = sprintf('issuer_discovery_pipe_%s', $id);
            $issuerDiscoveryPipeDefinition = (new Definition())
                ->setClass(Pipe::class)
                ->setArguments([[
                    new Reference($issuerDiscoveryId),
                ]])
                ->setPublic(true);
            $container->setDefinition($issuerDiscoveryPipeId, $issuerDiscoveryPipeDefinition);

            $route_loader = $container->getDefinition(RouteLoader::class);
            $route_loader->addMethodCall('addRoute', [
                $id,
                $issuerDiscoveryPipeId,
                'dispatch',
                $issuerDiscovery['path'],
                [], // defaults
                [], // requirements
                [], // options
                $issuerDiscovery['host'], // host
                ['https'], // schemes
                ['GET'], // methods
                '', // condition
            ]);
        }
    }
}
