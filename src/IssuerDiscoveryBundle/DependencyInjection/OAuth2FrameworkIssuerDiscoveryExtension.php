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

namespace OAuth2Framework\IssuerDiscoveryBundle\DependencyInjection;

use OAuth2Framework\Component\IssuerDiscoveryEndpoint\IdentifierResolver\IdentifierResolver;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OAuth2FrameworkIssuerDiscoveryExtension extends Extension
{
    /**
     * @var string
     */
    private $alias;

    /**
     * OAuth2FrameworkIssuerDiscoveryExtension constructor.
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

        $container->registerForAutoconfiguration(IdentifierResolver::class)->addTag('issuer_discovery_identifier_resolver');
        $container->setAlias('issuer_discovery.response_factory', $config['response_factory']);
        $container->setParameter('issuer_discovery.endpoints', $config['endpoints']);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../Resources/config/'));
        $loader->load('services.php');
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration($this->alias);
    }
}
