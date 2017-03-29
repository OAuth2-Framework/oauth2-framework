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

namespace OAuth2Framework\Bundle\Server\RefreshTokenGrantTypePlugin\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RefreshTokenConfigurationCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('oauth2_server.refresh_token.manager.default')) {
            return;
        }

        $definition = $container->getDefinition('oauth2_server.refresh_token.manager.default');
        $options = [
            'setRefreshTokenMaxLength'    => 'oauth2_server.refresh_token.max_length',
            'setRefreshTokenMinLength'    => 'oauth2_server.refresh_token.min_length',
            'setRefreshTokenLifetime'     => 'oauth2_server.refresh_token.lifetime',
        ];

        foreach ($options as $method => $value) {
            $definition->addMethodCall($method, [$container->getParameter($value)]);
        }
    }
}
