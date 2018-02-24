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

namespace OAuth2Framework\ServerBundle\Component\Endpoint\Authorization;

use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use OAuth2Framework\ServerBundle\Component\Component;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class FormPostResponseModeSource implements Component
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $configs['endpoint']['authorization']['response_mode']['form_post'];
        $container->setParameter('oauth2_server.endpoint.authorization.response_mode.form_post.enabled', $config['enabled']);
        if (!$config['enabled']) {
            return;
        }
        $container->setParameter('oauth2_server.endpoint.authorization.response_mode.form_post.template', $config['template']);
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/endpoint/authorization'));
        $loader->load('form_post_response_mode.php');
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'form_post';
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode)
    {
        $node->children()
            ->arrayNode($this->name())
                ->canBeEnabled()
                ->children()
                    ->scalarNode('template')
                        ->info('The template used to render the form.')
                        ->defaultValue('@OAuth2FrameworkServerBundle/form_post/response.html.twig')
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

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
    }
}
