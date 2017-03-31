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

namespace OAuth2Framework\Bundle\Server\SimpleStringAccessTokenPlugin\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SimpleStringAccessTokenConfigurationCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('oauth2_server.simple_string_access_token.manager.default')) {
            return;
        }

        $definition = $container->getDefinition('oauth2_server.simple_string_access_token.manager.default');
        $options = [
            'setAccessTokenMaxLength' => 'oauth2_server.simple_string_access_token.max_length',
            'setAccessTokenMinLength' => 'oauth2_server.simple_string_access_token.min_length',
            'setAccessTokenLifetime' => 'oauth2_server.simple_string_access_token.lifetime',
        ];

        foreach ($options as $method => $value) {
            $definition->addMethodCall($method, [$container->getParameter($value)]);
        }
    }
}
