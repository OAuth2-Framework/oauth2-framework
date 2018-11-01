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

namespace OAuth2Framework\ServerBundle\DependencyInjection;

use OAuth2Framework\ServerBundle\Component\Component;
use OAuth2Framework\ServerBundle\Doctrine\Type as DbalType;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

final class OAuth2FrameworkExtension extends Extension implements PrependExtensionInterface
{
    /**
     * @var Component[]
     */
    private $components;

    private $alias;

    /**
     * @param Component[] $components
     */
    public function __construct(string $alias, array $components)
    {
        $this->alias = $alias;
        $this->components = $components;
    }

    public function getAlias()
    {
        return $this->alias;
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        $processor = new Processor();
        $config = $processor->processConfiguration($this->getConfiguration($configs, $container), $configs);

        foreach ($this->components as $component) {
            $component->load($config, $container);
        }
    }

    public function getConfiguration(array $configs, ContainerBuilder $container): Configuration
    {
        return new Configuration($this->getAlias(), $this->components);
    }

    public function prepend(ContainerBuilder $container)
    {
        $this->prependComponents($container);
        $this->prependDoctrineTypes($container);
    }

    private function prependComponents(ContainerBuilder $container): void
    {
        $configs = $container->getExtensionConfig($this->getAlias());
        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);

        foreach ($this->components as $component) {
            $result = $component->prepend($container, $config);
            if (!empty($result)) {
                $container->prependExtensionConfig($this->getAlias(), $result);
            }
        }
    }

    private function prependDoctrineTypes(ContainerBuilder $container): void
    {
        $bundles = $container->getParameter('kernel.bundles');
        if (!\is_array($bundles) || !array_key_exists('DoctrineBundle', $bundles)) {
            return;
        }
        $configs = $container->getExtensionConfig('doctrine');
        if (empty($configs)) {
            return;
        }

        $config = current($configs);
        if (!isset($config['dbal'])) {
            $config['dbal'] = [];
        }
        if (!isset($config['dbal']['types'])) {
            $config['dbal']['types'] = [];
        }
        $config['dbal']['types'] += [
            'client_id' => DbalType\ClientIdType::class,
            'access_token_id' => DbalType\AccessTokenIdType::class,
            'user_account_id' => DbalType\UserAccountIdType::class,
            'resource_owner_id' => DbalType\ResourceOwnerIdType::class,
            'resource_server_id' => DbalType\ResourceServerIdType::class,
            'refresh_token_id' => DbalType\RefreshTokenIdType::class,
            'authorization_code_id' => DbalType\AuthorizationCodeIdType::class,
            'databag' => DbalType\DataBagType::class,
        ];

        $container->prependExtensionConfig('doctrine', $config);
    }
}
