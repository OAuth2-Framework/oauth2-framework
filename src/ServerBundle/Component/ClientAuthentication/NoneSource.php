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

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $container->setParameter('oauth2_server.client_authentication.none.enabled', $configs['client_authentication']['none']['enabled']);
        if ($configs['client_authentication']['none']['enabled']) {
            $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../Resources/config/client_authentication'));
            $loader->load('none.php');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode)
    {
        $node->children()
            ->arrayNode($this->name())
            ->info('The "none" authentication method is designed for public clients')
            ->canBeEnabled()
            ->end()
            ->end();
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container, array $config): array
    {
        return [];
    }
}
