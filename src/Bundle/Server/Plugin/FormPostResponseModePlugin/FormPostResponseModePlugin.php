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

namespace OAuth2Framework\Bundle\Server\FormPostResponseModePlugin;

use Assert\Assertion;
use Matthias\BundlePlugins\BundlePlugin;
use OAuth2Framework\Bundle\Server\CommonPluginMethod;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class FormPostResponseModePlugin extends CommonPluginMethod implements BundlePlugin, PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function name()
    {
        return 'form_post_response_mode';
    }

    /**
     * {@inheritdoc}
     */
    public function addConfiguration(ArrayNodeDefinition $pluginNode)
    {
        $pluginNode
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('template')
                    ->info('The template')
                    ->defaultValue('@OAuth2FrameworkServerBundle/form_post/response.html.twig')
                    ->cannotBeEmpty()
                ->end()
            ->end();
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $pluginConfiguration, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/Resources/config'));

        $parameters = [
            'oauth2_server.form_post_response_mode.template' => ['type' => 'parameter', 'path' => '[template]'],
        ];

        $this->loadParameters($parameters, $pluginConfiguration, $container);

        $loader->load('form_post_response_mode.yml');
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
    public function boot(ContainerInterface $container)
    {
        $container->get('twig.loader')->addPath(__DIR__.DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'views', 'OAuth2FrameworkServerBundle');
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        $bundle_config = current($container->getExtensionConfig('oauth2_server'));
        Assertion::keyExists($bundle_config, 'authorization_endpoint', 'The "AuthorizationEndpointPlugin" must be enabled to allow the use of the "FormPostResponseModePlugin".');
        Assertion::true($bundle_config['authorization_endpoint']['option']['allow_response_mode_parameter'], 'The option "oauth2_server.authorization_endpoint.option.allow_response_mode_parameter" must be set to true to allow the use of the "FormPostResponseModePlugin".');
    }
}
