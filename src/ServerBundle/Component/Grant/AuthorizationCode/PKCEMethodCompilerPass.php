<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\ServerBundle\Component\Grant\AuthorizationCode;

use OAuth2Framework\Component\AuthorizationCodeGrant\PKCEMethod\PKCEMethodManager;
use OAuth2Framework\ServerBundle\Service\MetadataBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class PKCEMethodCompilerPass implements CompilerPassInterface
{
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
                if (!\array_key_exists('alias', $attributes)) {
                    throw new \InvalidArgumentException(\Safe\sprintf('The PKCE method  "%s" does not have any "alias" attribute.', $id));
                }
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
