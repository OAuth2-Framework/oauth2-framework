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

namespace OAuth2Framework\Bundle\Server\AuthCodeGrantTypePlugin\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class AuthCodeGrantTypeConfigurationCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('oauth2_server.auth_code.grant_type')) {
            return;
        }

        $definition = $container->getDefinition('oauth2_server.auth_code.grant_type');

        $this->processEnabledOptions($container, $definition);
        $this->processAllowedOptions($container, $definition);
    }

    /**
     * @param ContainerBuilder $container
     * @param Definition       $definition
     */
    private function processEnabledOptions(ContainerBuilder $container, Definition $definition)
    {
        $options = [
            'PKCEForPublicClientsEnforcement' => 'oauth2_server.auth_code.enforce_pkce',
        ];
        foreach ($options as $method => $value) {
            $parameter = $container->getParameter($value);
            $call = sprintf('%s%s', true === $parameter ? 'enable' : 'disable', $method);
            $definition->addMethodCall($call);
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param Definition       $definition
     */
    private function processAllowedOptions(ContainerBuilder $container, Definition $definition)
    {
        $options = [
            'PublicClients' => 'oauth2_server.auth_code.allow_public_clients',
        ];
        foreach ($options as $method => $value) {
            $parameter = $container->getParameter($value);
            $call = sprintf('%sallow%s', true === $parameter ? '' : 'dis', $method);
            $definition->addMethodCall($call);
        }
    }
}
