<?php

declare(strict_types = 1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Bundle\Server\DependencyInjection\Source\TokenEndpointAuthMethod;

use Fluent\PhpConfigFileLoader;
use OAuth2Framework\Bundle\Server\DependencyInjection\Source\ArraySource;
use OAuth2Framework\Bundle\Server\DependencyInjection\Source\SourceInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class TokenEndpointAuthMethodSource extends ArraySource
{
    /**
     * @var SourceInterface[]
     */
    private $tokenEndpointAuthMethods = [];

    /**
     * TokenEndpointAuthMethodSource constructor.
     */
    public function __construct()
    {
        $this->tokenEndpointAuthMethods = [
            new ClientAssertionJwtTokenEndpointAuthMethodSource(),
            new ClientSecretBasicTokenEndpointAuthMethodSource(),
            new ClientSecretPostTokenEndpointAuthMethodSource(),
            new NoneTokenEndpointAuthMethodSource(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(array $bundleConfig, string $path, ContainerBuilder $container)
    {
        foreach ($this->tokenEndpointAuthMethods as $source) {
            $source->prepend($bundleConfig, $path.'['.$this->name().']', $container);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
        foreach ($this->tokenEndpointAuthMethods as $source) {
            $source->load($path, $container, $config);
        }

        $loader = new PhpConfigFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/token_endpoint_auth_method'));
        $loader->load('token_endpoint_auth_method.php');
    }

    /**
     * @return string
     */
    protected function name(): string
    {
        return 'token_endpoint_auth_method';
    }

    /**
     * {@inheritdoc}
     */
    protected function continueConfiguration(NodeDefinition $node)
    {
        parent::continueConfiguration($node);
        foreach ($this->tokenEndpointAuthMethods as $source) {
            $source->addConfiguration($node);
        }
    }
}
