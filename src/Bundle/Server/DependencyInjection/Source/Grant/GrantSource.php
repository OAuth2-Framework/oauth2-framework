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

namespace OAuth2Framework\Bundle\Server\DependencyInjection\Source\Grant;

use Fluent\PhpConfigFileLoader;
use OAuth2Framework\Bundle\Server\DependencyInjection\Source\ArraySource;
use OAuth2Framework\Bundle\Server\DependencyInjection\Source\SourceInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class GrantSource extends ArraySource
{
    /**
     * @var SourceInterface[]
     */
    private $grants = [];

    /**
     * TokenEndpointAuthMethodSource constructor.
     */
    public function __construct()
    {
        $this->grants = [
            new AuthorizationCodeSource(),
            new ClientCredentialsSource(),
            new ImplicitSource(),
            new NoneSource(),
            new ResourceOwnerPasswordCredentialSource(),
            new IdTokenSource(),
            new JwtBearerSource(),
            new RefreshTokenSource(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(array $bundleConfig, string $path, ContainerBuilder $container)
    {
        foreach ($this->grants as $source) {
            $source->prepend($bundleConfig, $path.'['.$this->name().']', $container);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
        foreach ($this->grants as $source) {
            $source->load($path, $container, $config);
        }

        $loader = new PhpConfigFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/grant'));
        $loader->load('grant.php');
    }

    /**
     * @return string
     */
    protected function name(): string
    {
        return 'grant';
    }

    /**
     * {@inheritdoc}
     */
    protected function continueConfiguration(NodeDefinition $node)
    {
        foreach ($this->grants as $source) {
            $source->addConfiguration($node);
        }
    }
}
