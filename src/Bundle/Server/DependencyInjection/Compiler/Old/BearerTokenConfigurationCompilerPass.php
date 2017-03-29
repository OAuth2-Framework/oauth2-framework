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

namespace OAuth2Framework\Bundle\Server\BearerTokenPlugin\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class BearerTokenConfigurationCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('oauth2_server.bearer_token')) {
            return;
        }

        $definition = $container->getDefinition('oauth2_server.bearer_token');

        $options = [
            'TokenFromAuthorizationHeader' => 'oauth2_server.bearer_token.authorization_header',
            'TokenFromQueryString'         => 'oauth2_server.bearer_token.query_string',
            'TokenFromRequestBody'         => 'oauth2_server.bearer_token.request_body',
        ];

        foreach ($options as $method => $param) {
            $option = $container->getParameter($param);
            $definition->addMethodCall(sprintf('%s%s', $option ? 'allow' : 'disallow', $method));
        }
    }
}
