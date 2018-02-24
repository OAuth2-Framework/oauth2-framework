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

namespace OAuth2Framework\ServerBundle\Component\ClientAuthentication;

use Jose\Bundle\JoseFramework\Helper\ConfigurationHelper;
use OAuth2Framework\ServerBundle\Component\ClientAuthentication\Compiler\ClientAssertionEncryptedJwtCompilerPass;
use OAuth2Framework\ServerBundle\Component\ClientAuthentication\Compiler\ClientAssertionJkuSupportCompilerPass;
use OAuth2Framework\ServerBundle\Component\ClientAuthentication\Compiler\ClientAssertionTrustedIssuerSupportCompilerPass;
use OAuth2Framework\ServerBundle\Component\ClientAuthentication\Compiler\ClientJwtAssertionMetadataCompilerPass;
use OAuth2Framework\ServerBundle\Component\Component;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class ClientAssertionJwtSource implements Component
{
    /**
     * @return string
     */
    public function name(): string
    {
        return 'client_assertion_jwt';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $configs['client_authentication']['client_assertion_jwt'];
        $container->setParameter('oauth2_server.client_authentication.client_assertion_jwt.enabled', $config['enabled']);
        if (!$config['enabled']) {
            return;
        }

        $keys = ['secret_lifetime', 'signature_algorithms', 'claim_checkers', 'header_checkers', 'jku_support'];
        foreach ($keys as $key) {
            $container->setParameter('oauth2_server.client_authentication.client_assertion_jwt.'.$key, $config[$key]);
        }

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../Resources/config/client_authentication'));
        $loader->load('client_assertion_jwt.php');

        $container->setParameter('oauth2_server.client_authentication.client_assertion_jwt.encryption.enabled', $config['encryption']['enabled']);
        if (!$config['encryption']['enabled']) {
            return;
        }

        $config = $configs['client_authentication']['client_assertion_jwt']['encryption'];
        $container->setParameter('oauth2_server.client_authentication.client_assertion_jwt.encryption.enabled', $config['enabled']);
        $keys = ['required', 'key_set', 'key_encryption_algorithms', 'content_encryption_algorithms'];
        foreach ($keys as $key) {
            $container->setParameter('oauth2_server.client_authentication.client_assertion_jwt.encryption.'.$key, $config[$key]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode)
    {
        $node->children()
            ->arrayNode($this->name())
                ->canBeEnabled()
                ->info('This method comprises the "client_secret_jwt" and the "private_key_jwt" authentication methods')
                ->validate()
                    ->ifTrue(function ($config) {
                        return $config['enabled'] && empty($config['signature_algorithms']);
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
                        ->scalarPrototype()->end()
                        ->treatNullLike([])
                    ->end()
                    ->arrayNode('claim_checkers')
                        ->info('Claim checkers for incoming assertions.')
                        ->useAttributeAsKey('name')
                        ->scalarPrototype()->end()
                        ->treatNullLike([])
                    ->end()
                    ->arrayNode('header_checkers')
                        ->info('Header checkers for incoming assertions.')
                        ->useAttributeAsKey('name')
                        ->scalarPrototype()->end()
                        ->treatNullLike([])
                    ->end()
                    ->booleanNode('jku_support')
                        ->info('If true, the client configuration parameter "jwks_uri" will be allowed.')
                        ->defaultTrue()
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
                            ->scalarNode('key_set')
                                ->info('Private or shared keys used for assertion decryption.')
                                ->isRequired()
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
    public function prepend(ContainerBuilder $container, array $configs): array
    {
        $config = $configs['client_authentication']['client_assertion_jwt'];
        if (!$config['enabled']) {
            return [];
        }
        ConfigurationHelper::addJWSVerifier($container, 'client_authentication.client_assertion_jwt', $config['signature_algorithms'], false, []);
        ConfigurationHelper::addHeaderChecker($container, 'client_authentication.client_assertion_jwt', $config['header_checkers'], false, []);
        ConfigurationHelper::addClaimChecker($container, 'client_authentication.client_assertion_jwt', $config['claim_checkers'], false, []);
        if ($config['encryption']['enabled']) {
            ConfigurationHelper::addJWELoader($container, 'client_authentication.client_assertion_jwt.encryption', ['jwe_compact'], $config['encryption']['key_encryption_algorithms'], $config['encryption']['content_encryption_algorithms'], ['DEF'], [] /*FIXME*/, false, []);
            ConfigurationHelper::addKeyset($container, 'client_authentication.client_assertion_jwt.encryption', 'jwkset', ['value' => $config['encryption']['key_set']], false, []);
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ClientJwtAssertionMetadataCompilerPass());
        $container->addCompilerPass(new ClientAssertionTrustedIssuerSupportCompilerPass());
        $container->addCompilerPass(new ClientAssertionJkuSupportCompilerPass());
        $container->addCompilerPass(new ClientAssertionEncryptedJwtCompilerPass());
    }
}
