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

namespace OAuth2Framework\Bundle\Server\DependencyInjection;

use Fluent\PhpConfigFileLoader;
use OAuth2Framework\Bundle\Server\DependencyInjection\Source\AccessTokenRepositorySource;
use OAuth2Framework\Bundle\Server\DependencyInjection\Source\ClientSource;
use OAuth2Framework\Bundle\Server\DependencyInjection\Source\Endpoint\EndpointSource;
use OAuth2Framework\Bundle\Server\DependencyInjection\Source\Grant\GrantSource;
use OAuth2Framework\Bundle\Server\DependencyInjection\Source\OpenIdConnect\OpenIdConnectSource;
use OAuth2Framework\Bundle\Server\DependencyInjection\Source\ResourceServerRepositorySource;
use OAuth2Framework\Bundle\Server\DependencyInjection\Source\Scope\ScopeSource;
use OAuth2Framework\Bundle\Server\DependencyInjection\Source\ServerNameSource;
use OAuth2Framework\Bundle\Server\DependencyInjection\Source\SourceInterface;
use OAuth2Framework\Bundle\Server\DependencyInjection\Source\TokenEndpointAuthMethod\TokenEndpointAuthMethodSource;
use OAuth2Framework\Bundle\Server\DependencyInjection\Source\TokenType\TokenTypeSource;
use OAuth2Framework\Bundle\Server\DependencyInjection\Source\UserAccountRepositorySource;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

final class OAuth2FrameworkServerExtension extends Extension implements PrependExtensionInterface
{
    /**
     * @var Source\SourceInterface[]
     */
    private $sourceMap;

    /**
     * @var string
     */
    private $alias;

    /**
     * OAuth2FrameworkServerExtension constructor.
     *
     * @param string $alias
     */
    public function __construct(string $alias)
    {
        $this->alias = $alias;
        $this->initSourceMap();
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration($this->alias, $this->sourceMap);
        $mergedConfig = $this->processConfiguration($configuration, $configs);
        $path = 'oauth2_server';
        $this->loadSources($path, $this->sourceMap, $mergedConfig, $container);
        $this->loadInternal($mergedConfig, $container);
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        $bundleConfig = current($container->getExtensionConfig($this->getAlias()));
        $this->prependSources($bundleConfig, '', $this->sourceMap, $container);
    }

    /**
     * @param string            $path
     * @param SourceInterface[] $sources
     * @param array             $mergedConfig
     * @param ContainerBuilder  $container
     */
    private function loadSources(string $path, array $sources, array $mergedConfig, ContainerBuilder $container)
    {
        foreach ($sources as $k => $source) {
            if ($source instanceof SourceInterface) {
                $source->load($path, $container, $mergedConfig);
            } elseif (is_string($k) && is_array($source)) {
                $this->loadSources($path.'.'.$k, $source, $mergedConfig[$k], $container);
            }
        }
    }

    /**
     * @param array             $bundleConfig
     * @param string            $path
     * @param SourceInterface[] $sources
     * @param ContainerBuilder  $container
     */
    private function prependSources(array $bundleConfig, string $path, array $sources, ContainerBuilder $container)
    {
        foreach ($sources as $k => $source) {
            if ($source instanceof SourceInterface) {
                $source->prepend($bundleConfig, $path, $container);
            } elseif (is_string($k) && is_array($source)) {
                $this->prependSources($bundleConfig, $path.'['.$k.']', $source, $container);
            }
        }
    }

    /**
     * @param array            $mergedConfig
     * @param ContainerBuilder $container
     */
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container)
    {
        $loader = new PhpConfigFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $files = [
            'service',
            'access_token',
            'access_token_handler',
            'route_loader',
            'token_type_hint',
            'oauth2_response',
            'user_account_discovery',
        ];
        foreach ($files as $basename) {
            $loader->load(sprintf('%s.php', $basename));
        }
    }

    private function initSourceMap()
    {
        $this->sourceMap = [
            new ClientSource(),
            new ServerNameSource(),
            new AccessTokenRepositorySource(),
            new UserAccountRepositorySource(),
            new ResourceServerRepositorySource(),
            new TokenTypeSource(),
            new TokenEndpointAuthMethodSource(),
            new GrantSource(),
            new EndpointSource(),
            new ScopeSource(),
            new OpenIdConnectSource(),
        ];
    }
}
