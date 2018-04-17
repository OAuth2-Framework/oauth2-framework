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

use OAuth2Framework\SecurityBundle\Annotation\Checker\Checker;
use OAuth2Framework\SecurityBundle\Tests\TestBundle\Service\AccessTokenHandler;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OAuth2Extension extends Extension
{
    /**
     * @var string
     */
    private $alias;

    /**
     * OAuth2Extension constructor.
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
        $container->registerForAutoconfiguration(Checker::class)->addTag('oauth2_security_annotation_checker');
        $container->registerForAutoconfiguration(AccessTokenHandler::class)->addTag('oauth2_access_token_handler');

        //$processor = new Processor();
        //$config = $processor->processConfiguration($this->getConfiguration($configs, $container), $configs);
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../Resources/config/'));
        $loader->load('services.php');

        $container->setAlias('oauth2.security.token_type_manager', 'oauth2.security.token_type_manager.default');
    }

    /**
     * @param array            $configs
     * @param ContainerBuilder $container
     *
     * @return Configuration
     */
    public function getConfiguration(array $configs, ContainerBuilder $container): Configuration
    {
        return new Configuration($this->getAlias());
    }
}
