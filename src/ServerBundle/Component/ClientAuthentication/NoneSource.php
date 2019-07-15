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

namespace OAuth2Framework\ServerBundle\Component\ClientAuthentication;

use OAuth2Framework\ServerBundle\Component\Component;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class NoneSource implements Component
{
    public function name(): string
    {
        return 'none';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $container->setParameter('oauth2_server.client_authentication.none.enabled', $configs['client_authentication']['none']['enabled']);
        if ($configs['client_authentication']['none']['enabled']) {
            $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../Resources/config/client_authentication'));
            $loader->load('none.php');
        }
    }

    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode): void
    {
        $node->children()
            ->arrayNode($this->name())
            ->info('The "none" authentication method is designed for public clients')
            ->canBeEnabled()
            ->end()
            ->end()
        ;
    }

    public function build(ContainerBuilder $container): void
    {
    }

    public function prepend(ContainerBuilder $container, array $config): array
    {
        return [];
    }
}
