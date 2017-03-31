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

namespace OAuth2Framework\Bundle\Server\MacTokenPlugin\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class MacTokenConfigurationCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('oauth2_server.mac_token')) {
            return;
        }

        $definition = $container->getDefinition('oauth2_server.mac_token');
        $options = [
            'setMacKeyMaxLength' => 'oauth2_server.mac_token.max_length',
            'setMacKeyMinLength' => 'oauth2_server.mac_token.min_length',
            'setMacAlgorithm' => 'oauth2_server.mac_token.algorithm',
            'setTimestampLifetime' => 'oauth2_server.mac_token.timestamp_lifetime',
        ];

        foreach ($options as $method => $value) {
            $definition->addMethodCall($method, [$container->getParameter($value)]);
        }
    }
}
