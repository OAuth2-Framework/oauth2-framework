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

namespace OAuth2Framework\Bundle\DependencyInjection\Component\OpenIdConnect;

use OAuth2Framework\Bundle\DependencyInjection\Component\Component;
use OAuth2Framework\Bundle\DependencyInjection\Component\Grant\AuthorizationCode\AuthorizationCodeSource;
use OAuth2Framework\Bundle\DependencyInjection\Component\Grant\ClientCredentials\ClientCredentialsSource;
use OAuth2Framework\Bundle\DependencyInjection\Component\Grant\Implicit\ImplicitSource;
use OAuth2Framework\Bundle\DependencyInjection\Component\Grant\JwtBearer\JwtBearerSource;
use OAuth2Framework\Bundle\DependencyInjection\Component\Grant\None\NoneSource;
use OAuth2Framework\Bundle\DependencyInjection\Component\Grant\RefreshToken\RefreshTokenSource;
use OAuth2Framework\Bundle\DependencyInjection\Component\Grant\ResourceOwnerPasswordCredential\ResourceOwnerPasswordCredentialSource;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType;
use OAuth2Framework\Component\TokenEndpoint\GrantType;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class PairwiseSubjectSource implements Component
{
    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'pairwise_subject';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        if (!$configs['openid_connect']['pairwise_subject']['enabled']) {
            return;
        }

        $container->setAlias('oauth2_server.openid_connect.pairwise.service', $configs['openid_connect']['pairwise_subject']['service']);
        $container->setParameter('oauth2_server.openid_connect.pairwise.is_default', $configs['openid_connect']['pairwise_subject']['is_default']);
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(NodeDefinition $node)
    {
        $node->children()
            ->arrayNode($this->name())
                ->canBeEnabled()
                ->addDefaultsIfNotSet()
                ->validate()
                    ->ifTrue(function ($config) {
                        return true === $config['enabled'] && empty($config['service']);
                    })
                    ->thenInvalid('The pairwise subject service must be set.')
                ->end()
                ->children()
                    ->scalarNode('service')
                    ->end()
                    ->booleanNode('is_default')
                        ->defaultTrue()
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
}
