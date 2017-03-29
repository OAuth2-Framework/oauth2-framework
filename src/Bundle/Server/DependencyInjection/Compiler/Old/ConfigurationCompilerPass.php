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

namespace OAuth2Framework\Bundle\Server\ImplicitGrantTypePlugin\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class ConfigurationCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('oauth2_server.implicit_grant_type')) {
            return;
        }

        $definition = $container->getDefinition('oauth2_server.implicit_grant_type');
        $this->processAllowedOptions($container, $definition);
    }

    /**
     * @param ContainerBuilder $container
     * @param Definition       $definition
     */
    private function processAllowedOptions(ContainerBuilder $container, Definition $definition)
    {
        $options = [
            'ConfidentialClients' => 'oauth2_server.implicit_grant_type.allow_confidential_clients',
        ];
        foreach ($options as $method => $value) {
            $parameter = $container->getParameter($value);
            $call = sprintf('%sallow%s', true === $parameter ? '' : 'dis', $method);
            $definition->addMethodCall($call);
        }
    }
}
