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

namespace OAuth2Framework\ServerBundle\Component\Core;

use OAuth2Framework\Component\Core\ResourceServer\ResourceServerRepository;
use OAuth2Framework\Component\ResourceServerAuthentication\AuthenticationMethodManager;
use OAuth2Framework\ServerBundle\Component\Component;
use OAuth2Framework\ServerBundle\Component\Core\Compiler\ResourceServerAuthenticationMethodCompilerPass;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class ResourceServerSource implements Component
{
    public function name(): string
    {
        return 'resource_server';
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        $container->registerForAutoconfiguration(AuthenticationMethodManager::class)->addTag('resource_server_authentication_method');

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../Resources/config/resource_server'));
        $loader->load('resource_server.php');

        if (null === $configs['resource_server']['repository']) {
            return;
        }
        $container->setAlias(ResourceServerRepository::class, $configs['resource_server']['repository']);
        $loader->load('authentication_middleware.php');
    }

    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode)
    {
        $node->children()
            ->arrayNode($this->name())
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('repository')
            ->info('The resource server repository service')
            ->defaultNull()
            ->end()
            ->end()
            ->end()
            ->end();
    }

    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ResourceServerAuthenticationMethodCompilerPass());
    }

    public function prepend(ContainerBuilder $container, array $config): array
    {
        return [];
    }
}
