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

namespace OAuth2Framework\Bundle\Component\ClientAuthentication;

use OAuth2Framework\Bundle\Component\Component;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class ClientSecretBasicSource implements Component
{
    /**
     * @return string
     */
    public function name(): string
    {
        return 'client_secret_basic';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        if ($configs['client_authentication']['client_secret_basic']['enabled']) {
            $container->setParameter('oauth2_server.client_authentication.client_secret_basic.realm', $configs['client_authentication']['client_secret_basic']['realm']);
            $container->setParameter('oauth2_server.client_authentication.client_secret_basic.secret_lifetime', $configs['client_authentication']['client_secret_basic']['secret_lifetime']);
            $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../Resources/config/client_authentication'));
            $loader->load('client_secret_basic.php');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(NodeDefinition $node)
    {
        $node->children()
            ->arrayNode($this->name())
                ->addDefaultsIfNotSet()
                ->canBeEnabled()
                ->children()
                    ->scalarNode('realm')
                        ->isRequired()
                        ->info('The realm displayed in the authentication header')
                    ->end()
                    ->integerNode('secret_lifetime')
                        ->defaultValue(60 * 60 * 24 * 14)
                        ->min(0)
                        ->info('Secret lifetime (in seconds; 0 = unlimited)')
                    ->end()
                ->end()
            ->end()
        ->end();
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        //Nothing to do
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container, array $config): array
    {
        //Nothing to do
        return [];
    }
}