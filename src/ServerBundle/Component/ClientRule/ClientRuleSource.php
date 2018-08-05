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

namespace OAuth2Framework\ServerBundle\Component\ClientRule;

use OAuth2Framework\Component\ClientRule\Rule;
use OAuth2Framework\ServerBundle\Component\Component;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class ClientRuleSource implements Component
{
    public function name(): string
    {
        return 'client_rule';
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        if (!\interface_exists(Rule::class)) {
            return;
        }
        $container->registerForAutoconfiguration(Rule::class)->addTag('oauth2_server_client_rule');
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../Resources/config/client_rule'));
        $loader->load('client_rule.php');
    }

    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode)
    {
    }

    public function prepend(ContainerBuilder $container, array $config): array
    {
        return [];
    }

    public function build(ContainerBuilder $container)
    {
        if (!\interface_exists(Rule::class)) {
            return;
        }
        $container->addCompilerPass(new ClientRuleCompilerPass());
    }
}
