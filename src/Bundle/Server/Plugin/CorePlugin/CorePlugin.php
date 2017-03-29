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

namespace OAuth2Framework\Bundle\Server\CorePlugin;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Matthias\BundlePlugins\BundlePlugin;
use OAuth2Framework\Bundle\Server\CommonPluginMethod;
use OAuth2Framework\Bundle\Server\CorePlugin\DependencyInjection\Compiler\ExceptionCompilerPass;
use OAuth2Framework\Bundle\Server\CorePlugin\DependencyInjection\Compiler\GrantTypeCompilerPass;
use OAuth2Framework\Bundle\Server\CorePlugin\DependencyInjection\Compiler\ResponseModeCompilerPass;
use OAuth2Framework\Bundle\Server\CorePlugin\DependencyInjection\Compiler\ResponseTypeCompilerPass;
use OAuth2Framework\Bundle\Server\CorePlugin\DependencyInjection\Compiler\TokenEndpointAuthMethodCompilerPass;
use OAuth2Framework\Bundle\Server\CorePlugin\DependencyInjection\Compiler\TokenUpdaterCompilerPass;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class CorePlugin extends CommonPluginMethod implements BundlePlugin, PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function name()
    {
        return 'core';
    }

    /**
     * {@inheritdoc}
     */
    public function addConfiguration(ArrayNodeDefinition $pluginNode)
    {
        $pluginNode
            ->isRequired()
            ->children()
                ->scalarNode('access_token_manager')->info('The access token manager.')->defaultNull()->end()
            ->end();
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $pluginConfiguration, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/Resources/config'));
        $files = [
            'services',
            'exception_factories',
            'token_endpoint_auth_method',
        ];

        foreach ($files as $basename) {
            $loader->load(sprintf('%s.yml', $basename));
        }

        $parameters = [];
        if (null !== $pluginConfiguration['access_token_manager']) {
            $parameters['oauth2_server.core.access_token_manager'] = ['type' => 'alias', 'path' => '[access_token_manager]'];
        }

        $this->loadParameters($parameters, $pluginConfiguration, $container);
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $mappings = [
            realpath(__DIR__.'/Resources/config/doctrine-mapping/resource-owner') => 'OAuth2Framework\Component\Server\ResourceOwner',
            realpath(__DIR__.'/Resources/config/doctrine-mapping/token')          => 'OAuth2Framework\Component\Server\Token',
            realpath(__DIR__.'/Resources/config/doctrine-mapping/user-account')   => 'OAuth2Framework\Component\Server\UserAccount',
            realpath(__DIR__.'/Resources/config/doctrine-mapping/client')         => 'OAuth2Framework\Component\Server\Client',
        ];
        if (class_exists('Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass')) {
            $container->addCompilerPass(DoctrineOrmMappingsPass::createYamlMappingDriver($mappings, []));
        }

        $container->addCompilerPass(new TokenEndpointAuthMethodCompilerPass());
        $container->addCompilerPass(new GrantTypeCompilerPass());
        $container->addCompilerPass(new ResponseTypeCompilerPass());
        $container->addCompilerPass(new ResponseModeCompilerPass());
        $container->addCompilerPass(new ExceptionCompilerPass());
        $container->addCompilerPass(new TokenUpdaterCompilerPass());
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $container)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        $config = current($container->getExtensionConfig('oauth2_server'));
        if (array_key_exists('token_endpoint', $config)) {
            foreach (['access_token_manager'] as $name) {
                $config[$this->name()][$name] = $config['token_endpoint'][$name];
            }
        } elseif (array_key_exists('implicit_grant_type', $config)) {
            foreach (['access_token_manager'] as $name) {
                $config[$this->name()][$name] = $config['implicit_grant_type'][$name];
            }
        }
        $container->prependExtensionConfig('oauth2_server', $config);
    }
}
