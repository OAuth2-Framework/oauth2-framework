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
use OAuth2Framework\Component\Server\Model\Scope\ScopeRepositoryInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ScopePolicyCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(ScopeRepositoryInterface::class)) {
            return;
        }

        $definition = $container->getDefinition(ScopeRepositoryInterface::class);
        //$default = $container->getParameter('oauth2_server.scope_manager.scope_policy');
        $default = 'none';

        $taggedServices = $container->findTaggedServiceIds('oauth2_server_scope_policy');
        $default_found = false;
        $policy_names = [];
        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                Assertion::keyExists($attributes, 'policy_name', sprintf("The scope policy '%s' does not have any 'policy_name' attribute.", $id));
                $is_default = $default === $attributes['policy_name'];
                $policy_names[] = $attributes['policy_name'];
                if (true === $is_default) {
                    $default_found = true;
                }
                $definition->addMethodCall('addScopePolicy', [new Reference($id), $is_default]);
            }
        }

        Assertion::true($default_found, sprintf('Unable to find the scope policy "%s". Available policies are: %s.', $default, json_encode($policy_names)));
    }
}
