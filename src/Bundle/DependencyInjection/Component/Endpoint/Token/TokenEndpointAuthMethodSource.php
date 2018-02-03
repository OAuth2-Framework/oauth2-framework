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

namespace OAuth2Framework\Bundle\DependencyInjection\Component\Endpoint\Token;

use OAuth2Framework\Bundle\DependencyInjection\Component\Component;
use OAuth2Framework\Component\TokenEndpoint\AuthenticationMethod\AuthenticationMethod;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

final class TokenEndpointAuthMethodSource implements Component
{
    /**
     * @return string
     */
    public function name(): string
    {
        return 'authentication';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $container->registerForAutoconfiguration(AuthenticationMethod::class)->addTag('oauth2_server_token_endpoint_auth_method');

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../../../Resources/config/token_endpoint_auth_method'));
        $loader->load('token_endpoint_auth_method.php');

        if ($configs['endpoint']['token']['authentication']['none']['enabled']) {
            $loader->load('none.php');
        }
        if ($configs['endpoint']['token']['authentication']['client_secret_basic']['enabled']) {
            $container->setParameter('oauth2_server.endpoint.token.authentication.client_secret_basic.realm', $configs['endpoint']['token']['authentication']['client_secret_basic']['realm']);
            $container->setParameter('oauth2_server.endpoint.token.authentication.client_secret_basic.secret_lifetime', $configs['endpoint']['token']['authentication']['client_secret_basic']['secret_lifetime']);
            $loader->load('client_secret_basic.php');
        }
        if ($configs['endpoint']['token']['authentication']['client_secret_post']['enabled']) {
            $container->setParameter('oauth2_server.endpoint.token.authentication.client_secret_post.secret_lifetime', $configs['endpoint']['token']['authentication']['client_secret_post']['secret_lifetime']);
            $loader->load('client_secret_post.php');
        }
        if ($configs['endpoint']['token']['authentication']['client_assertion_jwt']['enabled']) {
            $container->setParameter('oauth2_server.endpoint.token.authentication.client_assertion_jwt.secret_lifetime', $configs['endpoint']['token']['authentication']['client_assertion_jwt']['secret_lifetime']);
            $container->setParameter('oauth2_server.endpoint.token.authentication.client_assertion_jwt.signature_algorithms', $configs['endpoint']['token']['authentication']['client_assertion_jwt']['signature_algorithms']);
            $container->setParameter('oauth2_server.endpoint.token.authentication.client_assertion_jwt.claim_checkers', $configs['endpoint']['token']['authentication']['client_assertion_jwt']['claim_checkers']);
            $container->setParameter('oauth2_server.endpoint.token.authentication.client_assertion_jwt.header_checkers', $configs['endpoint']['token']['authentication']['client_assertion_jwt']['header_checkers']);
            $loader->load('client_assertion_jwt.php');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(NodeDefinition $node)
    {
        $node->children()
            ->arrayNode($this->name())
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('none')
                        ->info('The "none" authentication method is designed for public clients')
                        ->canBeEnabled()
                    ->end()
                    ->arrayNode('client_secret_basic')
                        ->canBeEnabled()
                        ->children()
                            ->scalarNode('realm')
                                ->isRequired()
                                ->info('The realm displayed in the authentication header')
                            ->end()
                            ->integerNode('secret_lifetime')
                                ->defaultValue(60 * 60 * 24 * 14)
                                ->min(0)
                                ->info('Secret lifetime (in seconds; 0 = unlimited)')
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('client_secret_post')
                        ->canBeEnabled()
                        ->children()
                            ->integerNode('secret_lifetime')
                                ->defaultValue(60 * 60 * 24 * 14)
                                ->min(0)
                                ->info('Secret lifetime (in seconds; 0 = unlimited)')
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('client_assertion_jwt')
                        ->canBeEnabled()
                        ->info('This method comprises the "client_secret_jwt" and the "private_key_jwt" authentication methods')
                        ->validate()
                            ->ifTrue(function ($config) {
                                return true === $config['enabled'] && empty($config['signature_algorithms']);
                            })
                            ->thenInvalid('At least one signature algorithm must be set.')
                        ->end()
                        ->children()
                            ->integerNode('secret_lifetime')
                                ->info('Secret lifetime (in seconds; 0 = unlimited) applicable to the "client_secret_jwt" authentication method')
                                ->defaultValue(60 * 60 * 24 * 14)
                                ->min(0)
                            ->end()
                            ->arrayNode('signature_algorithms')
                                ->info('Supported signature algorithms.')
                                ->useAttributeAsKey('name')
                                ->prototype('scalar')->end()
                                ->treatNullLike([])
                            ->end()
                            ->arrayNode('claim_checkers')
                                ->info('Claim checkers for incoming assertions.')
                                ->useAttributeAsKey('name')
                                ->prototype('scalar')->end()
                                ->treatNullLike([])
                            ->end()
                            ->arrayNode('header_checkers')
                                ->info('Header checkers for incoming assertions.')
                                ->useAttributeAsKey('name')
                                ->prototype('scalar')->end()
                                ->treatNullLike([])
                            ->end()
                            ->arrayNode('encryption')
                                ->canBeEnabled()
                                ->validate()
                                    ->ifTrue(function ($config) {
                                        return true === $config['enabled'] && empty($config['key_encryption_algorithms']);
                                    })
                                    ->thenInvalid('At least one key encryption algorithm must be set.')
                                ->end()
                                ->validate()
                                    ->ifTrue(function ($config) {
                                        return true === $config['enabled'] && empty($config['content_encryption_algorithms']);
                                    })
                                    ->thenInvalid('At least one content encryption algorithm must be set.')
                                ->end()
                                ->children()
                                    ->booleanNode('required')
                                        ->info('When true, all incoming assertions must be encrypted.')
                                        ->defaultFalse()
                                    ->end()
                                    ->arrayNode('key_encryption_algorithms')
                                        ->info('Supported key encryption algorithms.')
                                        ->useAttributeAsKey('name')
                                        ->prototype('scalar')->end()
                                        ->treatNullLike([])
                                    ->end()
                                    ->arrayNode('content_encryption_algorithms')
                                        ->info('Supported content encryption algorithms.')
                                        ->useAttributeAsKey('name')
                                        ->prototype('scalar')->end()
                                        ->treatNullLike([])
                                    ->end()
                                ->end()
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
    public function prepend(ContainerBuilder $container, array $config): array
    {
        //Nothing to do
        return [];
    }
}
