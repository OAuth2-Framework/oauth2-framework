<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\ServerBundle\DependencyInjection;

use Doctrine\DBAL\Types\Type;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeId;
use OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessTokenId;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenId;
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
    private array $components;

    private string $alias;

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

    public function load(array $configs, ContainerBuilder $container): void
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

    public function prepend(ContainerBuilder $container): void
    {
        $configs = $container->getExtensionConfig($this->getAlias());
        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);

        foreach ($this->components as $component) {
            $result = $component->prepend($container, $config);
            if (0 !== \count($result)) {
                $container->prependExtensionConfig($this->getAlias(), $result);
            }
        }
        $this->addDoctrineTypes($container);
    }

    /**
     * {@inheritdoc}
     */
    private function addDoctrineTypes(ContainerBuilder $container): void
    {
        $bundles = $container->getParameter('kernel.bundles');
        if (!\is_array($bundles) || !\array_key_exists('DoctrineBundle', $bundles) || !class_exists(Type::class)) {
            return;
        }
        $configs = $container->getExtensionConfig('doctrine');
        if (0 === \count($configs)) {
            return;
        }
        $config = current($configs);
        if (!isset($config['dbal'])) {
            $config['dbal'] = [];
        }
        if (!isset($config['dbal']['types'])) {
            $config['dbal']['types'] = [];
        }

        $newTypes = [
            'access_token_id' => DbalType\AccessTokenIdType::class,
            'client_id' => DbalType\ClientIdType::class,
            'databag' => DbalType\DatabagType::class,
            'resource_owner_id' => DbalType\ResourceOwnerIdType::class,
            'resource_server_id' => DbalType\ResourceServerIdType::class,
            'user_account_id' => DbalType\UserAccountIdType::class,
        ];
        if (class_exists(AuthorizationCodeId::class)) {
            $newTypes['authorization_code_id'] = DbalType\AuthorizationCodeIdType::class;
        }
        if (class_exists(InitialAccessTokenId::class)) {
            $newTypes['initial_access_token_id'] = DbalType\InitialAccessTokenIdType::class;
        }
        if (class_exists(RefreshTokenId::class)) {
            $newTypes['refresh_token_id'] = DbalType\RefreshTokenIdType::class;
        }

        $config['dbal']['types'] += $newTypes;
        $container->prependExtensionConfig('doctrine', $config);
    }
}
