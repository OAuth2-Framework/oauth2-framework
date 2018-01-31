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

namespace OAuth2Framework\Bundle\DependencyInjection\Component\TokenEndpointAuthMethod;

use Fluent\PhpConfigFileLoader;
use OAuth2Framework\Bundle\DependencyInjection\Component\Component;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class ClientSecretPostTokenEndpointAuthMethodSource implements Component
{
    /**
     * {@inheritdoc}
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
        $container->setParameter($path.'.secret_lifetime', $config['secret_lifetime']);

        $loader = new PhpConfigFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/token_endpoint_auth_method'));
        $loader->load('client_secret_post.php');
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'client_secret_post';
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(NodeDefinition $node)
    {
        $node
            ->children()
                ->integerNode('secret_lifetime')->defaultValue(60 * 60 * 24 * 14)->min(0)->end()
            ->end();
    }
}
