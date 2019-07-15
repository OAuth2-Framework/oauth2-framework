<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\ServerBundle\Component\Scope\Compiler;

use OAuth2Framework\Component\Scope\Policy\ScopePolicyManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ScopePolicyCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(ScopePolicyManager::class)) {
            return;
        }

        $definition = $container->getDefinition(ScopePolicyManager::class);
        $default = $container->getParameter('oauth2_server.scope.policy.by_default');

        $taggedServices = $container->findTaggedServiceIds('oauth2_server_scope_policy');
        $default_found = false;
        $policy_names = [];
        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                if (!\array_key_exists('policy_name', $attributes)) {
                    throw new \InvalidArgumentException(\Safe\sprintf('The scope policy "%s" does not have any "policy_name" attribute.', $id));
                }
                $is_default = $default === $attributes['policy_name'];
                $policy_names[] = $attributes['policy_name'];
                if (true === $is_default) {
                    $default_found = true;
                }
                $definition->addMethodCall('add', [new Reference($id), $is_default]);
            }
        }

        if (!$default_found) {
            throw new \InvalidArgumentException(\Safe\sprintf('Unable to find the scope policy "%s". Available policies are: %s.', $default, implode(', ', $policy_names)));
        }
    }
}
