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

namespace OAuth2Framework\SecurityBundle\DependencyInjection;

use OAuth2Framework\Component\BearerTokenType\BearerToken;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenHandler;
use OAuth2Framework\Component\MacTokenType\MacToken;
use OAuth2Framework\SecurityBundle\Annotation\Checker\Checker;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

final class OAuth2FrameworkSecurityExtension extends Extension
{
    /**
     * @var string
     */
    private $alias;

    /**
     * OAuth2FrameworkSecurityExtension constructor.
     *
     * @param string $alias
     */
    public function __construct(string $alias)
    {
        $this->alias = $alias;
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $processor = new Processor();
        $config = $processor->processConfiguration($this->getConfiguration($configs, $container), $configs);

        $container->setAlias('oauth2_security.psr7_message_factory', $config['psr7_message_factory']);

        $container->registerForAutoconfiguration(Checker::class)->addTag('oauth2_security_annotation_checker');
        $container->registerForAutoconfiguration(AccessTokenHandler::class)->addTag('oauth2_security_token_handler');

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../Resources/config/'));
        $loader->load('security.php');

        if (class_exists(BearerToken::class) && $config['bearer_token']['enabled']) {
            $container->setParameter('oauth2_security.token_type.bearer_token.realm', $config['bearer_token']['realm']);
            $container->setParameter('oauth2_security.token_type.bearer_token.authorization_header', $config['bearer_token']['authorization_header']);
            $container->setParameter('oauth2_security.token_type.bearer_token.request_body', $config['bearer_token']['request_body']);
            $container->setParameter('oauth2_security.token_type.bearer_token.query_string', $config['bearer_token']['query_string']);
            $loader->load('bearer_token.php');
        }
        if (class_exists(MacToken::class) && $config['mac_token']['enabled']) {
            $container->setParameter('oauth2_security.token_type.mac_token.min_length', $config['mac_token']['min_length']);
            $container->setParameter('oauth2_security.token_type.mac_token.max_length', $config['mac_token']['max_length']);
            $container->setParameter('oauth2_security.token_type.mac_token.algorithm', $config['mac_token']['algorithm']);
            $container->setParameter('oauth2_security.token_type.mac_token.timestamp_lifetime', $config['mac_token']['timestamp_lifetime']);
            $loader->load('mac_token.php');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration(array $configs, ContainerBuilder $container): Configuration
    {
        return new Configuration($this->getAlias());
    }
}