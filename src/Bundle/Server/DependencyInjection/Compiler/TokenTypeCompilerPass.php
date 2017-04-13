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

namespace OAuth2Framework\Bundle\Server\DependencyInjection\Compiler;

use Assert\Assertion;
use OAuth2Framework\Component\Server\TokenType\TokenTypeManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class TokenTypeCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(TokenTypeManager::class)) {
            return;
        }

        $definition = $container->getDefinition(TokenTypeManager::class);
        $default = $container->getParameter('oauth2_server.token_type.default');
        $taggedServices = $container->findTaggedServiceIds('oauth2_server_token_type');
        $default_found = false;
        $token_type_names = [];
        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                Assertion::keyExists($attributes, 'scheme', sprintf("The token type '%s' does not have any 'scheme' attribute.", $id));
                $is_default = $default === $attributes['scheme'];
                $token_type_names[] = $attributes['scheme'];
                if (true === $is_default) {
                    $default_found = true;
                }
                $definition->addMethodCall('add', [new Reference($id), $is_default]);
            }
        }
        Assertion::true($default_found, sprintf('Unable to find the token type "%s". Available token types are: %s.', $default, json_encode($token_type_names)));
    }
}
