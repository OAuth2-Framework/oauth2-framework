<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Bundle\Server\DependencyInjection\Source\OpenIdConnect;


use Fluent\PhpConfigFileLoader;
use Jose\Bundle\JoseFramework\Helper\ConfigurationHelper;
use OAuth2Framework\Bundle\Server\DependencyInjection\Source\ArraySource;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class IdTokenSource extends ArraySource
{
    /**
     * UserinfoSource constructor.
     */
    public function __construct()
    {
        $this->addSubSource(new IdTokenResponseTypeSource());
        $this->addSubSource(new IdTokenEncryptionSource());
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(array $bundleConfig, string $path, ContainerBuilder $container)
    {
        parent::prepend($bundleConfig, $path, $container);
        $currentPath = $path.'['.$this->name().']';
        $accessor = PropertyAccess::createPropertyAccessor();
        $sourceConfig = $accessor->getValue($bundleConfig, $currentPath);
        ConfigurationHelper::addJWSBuilder($container, $this->name(), $sourceConfig['signature_algorithms'], false);
        ConfigurationHelper::addJWSLoader($container, $this->name(), $sourceConfig['signature_algorithms'], [], ['jws_compact'], false);

        Assertion::keyExists($bundleConfig['key_set'], 'signature', 'The signature key set must be enabled.');
        //ConfigurationHelper::addKeyset($container, 'id_token.key_set.signature', 'jwkset', ['value' => $bundleConfig['key_set']['signature']]);
    }

    /**
     * {@inheritdoc}
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
        foreach (['lifetime', 'default_signature_algorithm', 'signature_algorithms', 'claim_checkers', 'header_checkers'] as $k) {
            $container->setParameter($path.'.'.$k, $config[$k]);
        }
        //$container->setAlias($path.'.key_set', 'jose.key_set.id_token.key_set.signature');

        $loader = new PhpConfigFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/openid_connect'));
        $loader->load('userinfo_scope_support.php');
    }

    /**
     * {@inheritdoc}
     */
    protected function name(): string
    {
        return 'id_token';
    }

    protected function continueConfiguration(NodeDefinition $node)
    {
        parent::continueConfiguration($node);
        $node
            ->validate()
                ->ifTrue(function ($config) {
                    return empty($config['default_signature_algorithm']);
                })
                ->thenInvalid('The option "default_signature_algorithm" must be set.')
            ->end()
            ->validate()
                ->ifTrue(function ($config) {
                    return empty($config['signature_algorithms']);
                })
                ->thenInvalid('The option "signature_algorithm" must contain at least one signature algorithm.')
            ->end()
            ->validate()
                ->ifTrue(function ($config) {
                    return !in_array($config['default_signature_algorithm'], $config['signature_algorithms']);
                })
                ->thenInvalid('The default signature algorithm must be in the supported signature algorithms.')
            ->end()
            ->children()
                ->scalarNode('default_signature_algorithm')
                    ->info('Signature algorithm used if the client has not defined a preferred one. Recommended value is "RS256".')
                ->end()
                ->arrayNode('signature_algorithms')
                    ->info('Signature algorithm used to sign the ID Tokens.')
                    ->useAttributeAsKey('name')
                    ->prototype('scalar')->end()
                    ->treatNullLike([])
                ->end()
                ->arrayNode('claim_checkers')
                    ->info('Checkers will verify the JWT claims.')
                    ->useAttributeAsKey('name')
                    ->prototype('scalar')->end()
                    ->treatNullLike(['exp', 'iat', 'nbf'])
                ->end()
                ->arrayNode('header_checkers')
                    ->info('Checkers will verify the JWT headers.')
                    ->useAttributeAsKey('name')
                    ->prototype('scalar')->end()
                    ->treatNullLike(['crit'])
                ->end()
                ->integerNode('lifetime')
                    ->info('Lifetime of the ID Tokens (in seconds). If an access token is issued with the ID Token, the lifetime of the access token is used instead of this value.')
                    ->defaultValue(3600)
                    ->min(1)
                ->end()
            ->end();
    }
}
