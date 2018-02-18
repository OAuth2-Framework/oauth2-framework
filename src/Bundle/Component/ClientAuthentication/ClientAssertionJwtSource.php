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

namespace OAuth2Framework\Bundle\Component\ClientAuthentication;

use Jose\Bundle\JoseFramework\Helper\ConfigurationHelper;
use OAuth2Framework\Bundle\Component\ClientAuthentication\Compiler\ClientAssertionEncryptedJwtCompilerPass;
use OAuth2Framework\Bundle\Component\ClientAuthentication\Compiler\ClientAssertionJkuSupportCompilerPass;
use OAuth2Framework\Bundle\Component\ClientAuthentication\Compiler\ClientAssertionTrustedIssuerSupportCompilerPass;
use OAuth2Framework\Bundle\Component\ClientAuthentication\Compiler\ClientJwtAssertionMetadataCompilerPass;
use OAuth2Framework\Bundle\Component\Component;
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
        $container->setParameter('oauth2_server.client_authentication.client_assertion_jwt.enabled', $configs['client_authentication']['client_assertion_jwt']['enabled']);
        if (!$configs['client_authentication']['client_assertion_jwt']['enabled']) {
            return;
        }
        $container->setParameter('oauth2_server.client_authentication.client_assertion_jwt.secret_lifetime', $configs['client_authentication']['client_assertion_jwt']['secret_lifetime']);
        $container->setParameter('oauth2_server.client_authentication.client_assertion_jwt.signature_algorithms', $configs['client_authentication']['client_assertion_jwt']['signature_algorithms']);
        $container->setParameter('oauth2_server.client_authentication.client_assertion_jwt.claim_checkers', $configs['client_authentication']['client_assertion_jwt']['claim_checkers']);
        $container->setParameter('oauth2_server.client_authentication.client_assertion_jwt.header_checkers', $configs['client_authentication']['client_assertion_jwt']['header_checkers']);
        $container->setParameter('oauth2_server.client_authentication.client_assertion_jwt.jku_support.enabled', $configs['client_authentication']['client_assertion_jwt']['jku_support']['enabled']);
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../Resources/config/client_authentication'));
        $loader->load('client_assertion_jwt.php');

        $container->setParameter('oauth2_server.client_authentication.client_assertion_jwt.encryption.enabled', $configs['client_authentication']['client_assertion_jwt']['encryption']['enabled']);
        if (!$configs['client_authentication']['client_assertion_jwt']['encryption']['enabled']) {
            return;
        }

        $container->setParameter('oauth2_server.client_authentication.client_assertion_jwt.encryption.required', $configs['client_authentication']['client_assertion_jwt']['encryption']['required']);
        $container->setParameter('oauth2_server.client_authentication.client_assertion_jwt.encryption.key_set', $configs['client_authentication']['client_assertion_jwt']['encryption']['key_set']);
        $container->setParameter('oauth2_server.client_authentication.client_assertion_jwt.encryption.key_encryption_algorithms', $configs['client_authentication']['client_assertion_jwt']['encryption']['key_encryption_algorithms']);
        $container->setParameter('oauth2_server.client_authentication.client_assertion_jwt.encryption.content_encryption_algorithms', $configs['client_authentication']['client_assertion_jwt']['encryption']['content_encryption_algorithms']);
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode)
    {
        $node->children()
            ->arrayNode($this->name())
                ->addDefaultsIfNotSet()
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
                    ->arrayNode('jku_support')
                        ->info('If enabled, the client configuration parameter "jwks_uri" will be allowed.')
                        ->canBeEnabled()
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
