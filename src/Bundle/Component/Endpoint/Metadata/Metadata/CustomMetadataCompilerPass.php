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

use OAuth2Framework\Bundle\Service\MetadataBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class CustomMetadataCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(MetadataBuilder::class)) {
            return;
        }

        $definition = $container->getDefinition(MetadataBuilder::class);
        $customRoutes = $container->getParameter('oauth2_server.endpoint.metadata.custom_routes');
        foreach ($customRoutes as $key => $parameters) {
            $this->addMethodCall($definition, 'setRoute', [$key, $parameters['route_name'], $parameters['route_parameters']]);
        }

        $customValues = $container->getParameter('oauth2_server.endpoint.metadata.custom_values');
        foreach ($customValues as $key => $parameters) {
            $this->addMethodCall($definition, 'addKeyValuePair', [$key, $parameters]);
        }
    }

    /**
     * @param Definition $definition
     * @param string     $method
     * @param array      $parameters
     */
    private function addMethodCall(Definition $definition, string $method, array $parameters)
    {
        $definition->addMethodCall($method, $parameters);
    }
}
