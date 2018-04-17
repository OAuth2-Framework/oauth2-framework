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

namespace OAuth2Framework\ServerBundle\Component\Grant\AuthorizationCode;

use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeIdGenerator;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeRepository;
use OAuth2Framework\ServerBundle\Component\Component;
use OAuth2Framework\ServerBundle\Service\RandomAuthorizationCodeIdGenerator;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class AuthorizationCodeSource implements Component
{
    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'authorization_code';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        if ($configs['grant']['authorization_code']['enabled']) {
            $container->setParameter('oauth2_server.grant.authorization_code.lifetime', $configs['grant']['authorization_code']['lifetime']);
            $container->setParameter('oauth2_server.grant.authorization_code.enforce_pkce', $configs['grant']['authorization_code']['enforce_pkce']);
            $container->setAlias(AuthorizationCodeRepository::class, $configs['grant']['authorization_code']['repository']);
            $container->setAlias(AuthorizationCodeIdGenerator::class, $configs['grant']['authorization_code']['id_generator']);

            $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/grant'));
            $loader->load('authorization_code.php');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode)
    {
        $node->children()
            ->arrayNode('authorization_code')
                ->canBeEnabled()
                ->children()
                    ->integerNode('lifetime')
                        ->defaultValue(30)
                        ->min(1)
                        ->info('Authorization code lifetime (in seconds)')
                    ->end()
                    ->scalarNode('repository')
                        ->isRequired()
                        ->info('The authorization code repository')
                    ->end()
                    ->scalarNode('id_generator')
                        ->info('The authorization code ID generator service')
                        ->defaultValue(RandomAuthorizationCodeIdGenerator::class)
                    ->end()
                    ->booleanNode('enforce_pkce')
                        ->defaultFalse()
                        ->info('If true, the PKCE is required for all requests including the ones from confidential clients')
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
        $container->addCompilerPass(new PKCEMethodCompilerPass());
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container, array $config): array
    {
        //Nothing to do
        return [];
    }
}
