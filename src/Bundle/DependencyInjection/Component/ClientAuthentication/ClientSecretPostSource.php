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

namespace OAuth2Framework\Bundle\DependencyInjection\Component\ClientAuthentication;

use OAuth2Framework\Bundle\DependencyInjection\Component\Component;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

final class ClientSecretPostSource implements Component
{
    /**
     * @return string
     */
    public function name(): string
    {
        return 'client_secret_post';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {

        if ($configs['client_authentication']['client_secret_post']['enabled']) {
            $container->setParameter('oauth2_server.client_authentication.client_secret_post.secret_lifetime', $configs['client_authentication']['client_secret_post']['secret_lifetime']);
            $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../../Resources/config/client_authentication'));
            $loader->load('client_secret_post.php');
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
    public function prepend(ContainerBuilder $container, array $config): array
    {
        return [];
    }
}
