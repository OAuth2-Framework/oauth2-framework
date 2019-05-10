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
use Symfony\Component\Config\Definition\ConfigurationInterface;
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

    public function __construct(string $alias)
    {
        $this->alias = $alias;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $processor = new Processor();
        $config = $processor->processConfiguration($this->getConfiguration($configs, $container), $configs);

        $container->setAlias('oauth2_security.psr7_message_factory', $config['psr7_message_factory']);

        $container->registerForAutoconfiguration(Checker::class)->addTag('oauth2_security_annotation_checker');
        $container->registerForAutoconfiguration(AccessTokenHandler::class)->addTag('oauth2_security_token_handler');

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../Resources/config/'));
        $loader->load('security.php');

        if (true === $config['bearer_token']['enabled'] && \class_exists(BearerToken::class)) {
            $container->setParameter('oauth2_security.token_type.bearer_token.realm', $config['bearer_token']['realm']);
            $loader->load('bearer_token.php');

            $bearerTokenConfig = $config['bearer_token'];
            if (true === $bearerTokenConfig['authorization_header']) {
                $loader->load('authorization_header_token_finder.php');
            }
            if (true === $bearerTokenConfig['query_string']) {
                $loader->load('query_string_token_finder.php');
            }
            if (true === $bearerTokenConfig['request_body']) {
                $loader->load('request_body_token_finder.php');
            }
        }
        if (\class_exists(MacToken::class) && $config['mac_token']['enabled']) {
            $container->setParameter('oauth2_security.token_type.mac_token.min_length', $config['mac_token']['min_length']);
            $container->setParameter('oauth2_security.token_type.mac_token.max_length', $config['mac_token']['max_length']);
            $container->setParameter('oauth2_security.token_type.mac_token.algorithm', $config['mac_token']['algorithm']);
            $container->setParameter('oauth2_security.token_type.mac_token.timestamp_lifetime', $config['mac_token']['timestamp_lifetime']);
            $loader->load('mac_token.php');
        }
    }

    public function getConfiguration(array $configs, ContainerBuilder $container): ConfigurationInterface
    {
        return new Configuration($this->getAlias());
    }
}
