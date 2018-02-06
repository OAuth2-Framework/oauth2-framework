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

namespace OAuth2Framework\Bundle\DependencyInjection\Component\Endpoint\ClientRegistration;

use Jose\Bundle\JoseFramework\Helper\ConfigurationHelper;
use OAuth2Framework\Bundle\DependencyInjection\Component\Component;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class SoftwareStatementSource implements Component
{
    /**
     * @return string
     */
    public function name(): string
    {
        return 'software_statement';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        if (!$configs['endpoint']['client_registration']['software_statement']['enabled']) {
            return;
        }
        $container->setParameter('oauth2_server.endpoint.client_registration.software_statement.required', $configs['endpoint']['client_registration']['software_statement']['required']);
        $container->setParameter('oauth2_server.endpoint.client_registration.software_statement.allowed_signature_algorithms', $configs['endpoint']['client_registration']['software_statement']['allowed_signature_algorithms']);
        $container->setParameter('oauth2_server.endpoint.client_registration.software_statement.key_set', $configs['endpoint']['client_registration']['software_statement']['key_set']);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../../../Resources/config/endpoint/client_registration'));
        $loader->load('software_statement.php');
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(NodeDefinition $node)
    {
        $node->children()
            ->arrayNode($this->name())
                ->addDefaultsIfNotSet()
                ->canBeEnabled()
                ->validate()
                    ->ifTrue(function ($config) {
                        return true === $config['enabled'] && empty($config['key_set']);
                    })
                    ->thenInvalid('The option "key_set" must be set.')
                ->end()
                ->validate()
                    ->ifTrue(function ($config) {
                        return true === $config['enabled'] && empty($config['allowed_signature_algorithms']);
                    })
                    ->thenInvalid('At least one signature algorithm must be set.')
                ->end()
                ->children()
                    ->booleanNode('required')->defaultFalse()->end()
                    ->scalarNode('key_set')->end()
                    ->arrayNode('allowed_signature_algorithms')
                        ->info('Signature algorithms allowed for the software statements. The algorithm "none" should not be used.')
                        ->useAttributeAsKey('name')
                        ->prototype('scalar')->end()
                        ->treatNullLike([])
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
        /*$currentPath = $path.'['.$this->name().']';
        $accessor = PropertyAccess::createPropertyAccessor();
        $sourceConfig = $accessor->getValue($bundleConfig, $currentPath);

        if (true === $sourceConfig['enabled']) {
            // FIXME
            ConfigurationHelper::addJWSLoader($container, $this->name(), $sourceConfig['allowed_signature_algorithms'], [], ['jws_compact'], false);
            ConfigurationHelper::addKeyset($container, 'client_registration_software_statement.key_set.signature', 'jwkset', ['value' => $sourceConfig['key_set']]);
        }*/
        return [];
    }
}
