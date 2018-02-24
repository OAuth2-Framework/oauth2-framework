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

namespace OAuth2Framework\ServerBundle\Component\OpenIdConnect;

use OAuth2Framework\ServerBundle\Component\Component;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class UserinfoEndpointSignatureSource implements Component
{
    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'signature';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        if (!$configs['openid_connect']['userinfo_endpoint']['signature']['enabled']) {
            return;
        }

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../Resources/config/openid_connect'));
        //$loader->load('userinfo_endpoint.php');
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode)
    {
        $node->children()
            ->arrayNode($this->name())
                ->canBeEnabled()
                ->validate()
                    ->ifTrue(function ($config) {
                        return true === $config['enabled'] && empty($config['signature_algorithms']);
                    })
                    ->thenInvalid('You must set at least one signature algorithm.')
                ->end()
                ->children()
                    ->arrayNode('signature_algorithms')
                        ->info('Signature algorithm used to sign the user information.')
                        ->useAttributeAsKey('name')
                        ->scalarPrototype()->end()
                        ->treatNullLike([])
                        ->treatFalseLike([])
                    ->end()
                ->end()
            ->end()
        ->end();
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        //Nothing to do
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container, array $config): array
    {
        /*Assertion::keyExists($bundleConfig['key_set'], 'signature', 'The signature key set must be enabled.');
        $currentPath = $path.'['.$this->name().']';
        $accessor = PropertyAccess::createPropertyAccessor();
        $sourceConfig = $accessor->getValue($bundleConfig, $currentPath);

        ConfigurationHelper::addJWSBuilder($container, 'oauth2_server.userinfo', $sourceConfig['signature_algorithms'], false);*/
        return [];
    }
}
