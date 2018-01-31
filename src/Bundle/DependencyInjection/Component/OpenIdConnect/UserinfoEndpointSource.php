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

namespace OAuth2Framework\Bundle\DependencyInjection\Component\OpenIdConnect;

use Fluent\PhpConfigFileLoader;
use OAuth2Framework\Bundle\DependencyInjection\Component\Component;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class UserinfoEndpointSource implements Component
{
    /**
     * UserinfoSource constructor.
     */
    public function __construct()
    {
        $this->addSubSource(new UserinfoEndpointSignatureSource());
        $this->addSubSource(new UserinfoEndpointEncryptionSource());
    }

    /**
     * {@inheritdoc}
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
        $container->setParameter($path.'.path', $config['path']);
        $loader = new PhpConfigFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/openid_connect'));
        $loader->load('userinfo_endpoint.php');
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'userinfo_endpoint';
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(NodeDefinition $node)
    {

        $node
            ->children()
                ->scalarNode('path')->defaultValue('/userinfo')->end()
            ->end();
    }
}
