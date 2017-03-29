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

namespace OAuth2Framework\Bundle\Server\SecurityPlugin;

use Matthias\BundlePlugins\BundlePlugin;
use OAuth2Framework\Bundle\Server\CommonPluginMethod;
use OAuth2Framework\Bundle\Server\SecurityPlugin\DependencyInjection\Compiler\AccessTokenHandlerCompilerPass;
use OAuth2Framework\Bundle\Server\SecurityPlugin\DependencyInjection\Compiler\CheckerCompilerPass;
use OAuth2Framework\Bundle\Server\SecurityPlugin\DependencyInjection\Security\Factory\OAuth2Factory;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class SecurityPlugin extends CommonPluginMethod implements BundlePlugin
{
    /**
     * {@inheritdoc}
     */
    public function name()
    {
        return 'security';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $pluginConfiguration, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/Resources/config'));
        $files = ['security', 'annotations', 'checkers', 'resolver'];
        foreach ($files as $basename) {
            $loader->load(sprintf('%s.yml', $basename));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addConfiguration(ArrayNodeDefinition $pluginNode)
    {
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
    public function build(ContainerBuilder $container)
    {
        /*
         * @var \Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension
         */
        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory(new OAuth2Factory());
        $container->addCompilerPass(new CheckerCompilerPass());
        $container->addCompilerPass(new AccessTokenHandlerCompilerPass());
    }
}
