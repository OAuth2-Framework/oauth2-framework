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

class ResponseTypeSource implements Component
{
    public function name(): string
    {
        return 'response_type';
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $configs['openid_connect']['response_type'];
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../Resources/config/openid_connect/response_type'));
        foreach ($config as $k => $v) {
            if ($config[$k]['enabled']) {
                $loader->load(\sprintf('%s.php', $k));
            }
        }
    }

    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode)
    {
        $node->children()
            ->arrayNode($this->name())
            ->addDefaultsIfNotSet()
            ->children()
            ->arrayNode('id_token')
            ->canBeEnabled()
            ->info('')
            ->end()
            ->arrayNode('id_token_token')
            ->canBeEnabled()
            ->info('')
            ->end()
            ->arrayNode('code_token')
            ->canBeEnabled()
            ->info('')
            ->end()
            ->arrayNode('code_id_token')
            ->canBeEnabled()
            ->info('')
            ->end()
            ->arrayNode('code_id_token_token')
            ->canBeEnabled()
            ->info('')
            ->end()
            ->end()
            ->end()
            ->end();
    }

    public function build(ContainerBuilder $container)
    {
        //Nothing to do
    }

    public function prepend(ContainerBuilder $container, array $config): array
    {
        /*
        $currentPath = $path.'['.$this->name().']';
        $accessor = PropertyAccess::createPropertyAccessor();
        $sourceConfig = $accessor->getValue($bundleConfig, $currentPath);
        ConfigurationHelper::addJWSBuilder($container, $this->name(), $sourceConfig['signature_algorithms'], false);
        ConfigurationHelper::addJWSLoader($container, $this->name(), $sourceConfig['signature_algorithms'], [], ['jws_compact'], false);

        Assertion::keyExists($bundleConfig['key_set'], 'signature', 'The signature key set must be enabled.');
        //ConfigurationHelper::addKeyset($container, 'id_token.key_set.signature', 'jwkset', ['value' => $bundleConfig['key_set']['signature']]);
         */
        return [];
    }
}
