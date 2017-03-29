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

namespace OAuth2Framework\Bundle\Server\OpenIdConnectPlugin\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class IdTokenMetadataCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('oauth2_server.openid_connect.id_token.manager') || !$container->hasDefinition('oauth2_server.openid_connect.metadata')) {
            return;
        }

        $definition = $container->getDefinition('oauth2_server.openid_connect.metadata');

        $definition->addMethodCall('setIdTokenManager', [new Reference('oauth2_server.openid_connect.id_token.manager')]);
        $definition->addMethodCall('set', ['issuer', $container->getParameter('oauth2_server.openid_connect.id_token.manager.issuer')]);
        $definition->addMethodCall('set', ['claim_types_supported', ['normal', 'aggregated', 'distributed']]);
        $definition->addMethodCall('set', ['claims_parameter_supported', true]);

        foreach (['claims_supported', 'claims_locales_supported'] as $name) {
            $param = $container->getParameter(sprintf('oauth2_server.openid_connect.%s', $name));
            if (!empty($param)) {
                $definition->addMethodCall('set', [$name, $param]);
            }
        }
    }
}
