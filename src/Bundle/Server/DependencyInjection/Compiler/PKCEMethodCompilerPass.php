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
use OAuth2Framework\Bundle\Server\Service\MetadataBuilder;
use OAuth2Framework\Component\Server\GrantType\PKCEMethod\PKCEMethodManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class PKCEMethodCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(PKCEMethodManager::class)) {
            return;
        }

        $definition = $container->getDefinition(PKCEMethodManager::class);

        $taggedServices = $container->findTaggedServiceIds('oauth2_server_pkce_method');
        $loaded = [];
        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                Assertion::keyExists($attributes, 'alias', sprintf('The PKCE method  "%s" does not have any "alias" attribute.', $id));
                $loaded[] = $attributes['alias'];
                $definition->addMethodCall('add', [new Reference($id)]);
            }
        }

        if (!$container->hasDefinition(MetadataBuilder::class)) {
            return;
        }

        $definition = $container->getDefinition(MetadataBuilder::class);
        $definition->addMethodCall('setCodeChallengeMethodsSupported', [new Reference(PKCEMethodManager::class)]);
    }
}
