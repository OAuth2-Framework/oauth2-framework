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

namespace OAuth2Framework\Bundle\Component\Grant\JwtBearer;

use Jose\Bundle\JoseFramework\Helper\ConfigurationHelper;
use OAuth2Framework\Bundle\Component\Component;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class JwtBearerSource implements Component
{
    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'jwt_bearer';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        if (!$configs['grant']['jwt_bearer']['enabled']) {
            return;
        }
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/grant'));
        $loader->load('jwt_bearer.php');
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
                    ->thenInvalid('The option "signature_algorithms" must contain at least one signature algorithm.')
                ->end()
                ->children()
                    ->booleanNode('issue_refresh_token')
                        ->info('If true, a refresh token will be issued with the access token (the refresh token grant type must be enabled).')
                    ->end()
                    ->arrayNode('signature_algorithms')
                        ->info('Signature algorithms supported by this grant type.')
                        ->useAttributeAsKey('name')
                        ->scalarPrototype()->end()
                        ->treatNullLike([])
                    ->end()
                    ->arrayNode('claim_checkers')
                        ->info('Checkers will verify the JWT claims.')
                        ->useAttributeAsKey('name')
                        ->scalarPrototype()->end()
                        ->treatNullLike(['exp', 'iat', 'nbf'])
                    ->end()
                    ->arrayNode('header_checkers')
                        ->info('Checkers will verify the JWT headers.')
                        ->useAttributeAsKey('name')
                        ->scalarPrototype()->end()
                        ->treatNullLike(['crit'])
                    ->end()
                    ->arrayNode('encryption')
                        ->canBeEnabled()
                        ->children()
                            ->booleanNode('required')
                                ->info('If set to true, all ID Token sent to the server must be encrypted.')
                                ->defaultFalse()
                            ->end()
                            ->arrayNode('key_encryption_algorithms')
                                ->info('Supported key encryption algorithms.')
                                ->useAttributeAsKey('name')
                                ->scalarPrototype()->end()
                                ->treatNullLike([])
                            ->end()
                            ->arrayNode('content_encryption_algorithms')
                                ->info('Supported content encryption algorithms.')
                                ->useAttributeAsKey('name')
                                ->scalarPrototype()->end()
                                ->treatNullLike([])
                            ->end()
                        ->end()
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
        //Nothing to do
        return [];
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $sourceConfig
     */
    private function updateJoseBundleConfigurationForVerifier(ContainerBuilder $container, array $sourceConfig)
    {
        ConfigurationHelper::addJWSLoader($container, $this->name(), $sourceConfig['signature_algorithms'], [], ['jws_compact'], false);
        ConfigurationHelper::addClaimChecker($container, $this->name(), [], false);
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $sourceConfig
     */
    private function updateJoseBundleConfigurationForDecrypter(ContainerBuilder $container, array $sourceConfig)
    {
        if (true === $sourceConfig['encryption']['enabled']) {
            //ConfigurationHelper::addKeyset($container, 'jwt_bearer.key_set.encryption', 'jwkset', ['value' => $bundleConfig['key_set']['encryption']]);
            ConfigurationHelper::addJWELoader($container, $this->name(), $sourceConfig['encryption']['key_encryption_algorithms'], $sourceConfig['encryption']['content_encryption_algorithms'], ['DEF'], [], ['jwe_compact'], false);
        }
    }
}
