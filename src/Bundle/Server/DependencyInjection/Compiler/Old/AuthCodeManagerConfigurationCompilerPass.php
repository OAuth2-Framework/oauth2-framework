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

class AuthCodeManagerConfigurationCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('oauth2_server.auth_code.manager.default')) {
            return;
        }

        $definition = $container->getDefinition('oauth2_server.auth_code.manager.default');
        $options = [
            'setAuthorizationCodeMaxLength' => 'oauth2_server.auth_code.max_length',
            'setAuthorizationCodeMinLength' => 'oauth2_server.auth_code.min_length',
            'setAuthorizationCodeLifetime' => 'oauth2_server.auth_code.lifetime',
        ];
        foreach ($options as $method => $value) {
            $definition->addMethodCall($method, [$container->getParameter($value)]);
        }
    }
}
