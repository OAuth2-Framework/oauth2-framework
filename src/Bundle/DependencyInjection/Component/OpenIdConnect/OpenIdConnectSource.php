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

namespace OAuth2Framework\Bundle\DependencyInjection\Component\OpenIdConnect;

use OAuth2Framework\Bundle\DependencyInjection\Component\Component;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class OpenIdConnectSource implements Component
{
    /**
     * @var Component[]
     */
    private $subComponents;

    public function __construct()
    {
        $this->subComponents = [
            new PairwiseSubjectSource(),
            new IdTokenSource(),
            new UserinfoEndpointSource(),
            new ResponseTypeSource(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'openid_connect';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        if (!$configs['openid_connect']['enabled']) {
            return;
        }

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/openid_connect'));
        //$loader->load('openid_connect.php');

        foreach ($this->subComponents as $subComponent) {
            $subComponent->load($configs, $container);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(NodeDefinition $node)
    {
        $childNode = $node->children()
            ->arrayNode($this->name())
                ->canBeEnabled()
                ->addDefaultsIfNotSet();

        foreach ($this->subComponents as $subComponent) {
            $subComponent->getNodeDefinition($childNode);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container, array $config): array
    {
        $updatedConfig = [];
        foreach ($this->subComponents as $subComponent) {
            $updatedConfig = array_merge(
                $updatedConfig,
                $subComponent->prepend($container, $config)
            );
        }

        return $updatedConfig;
    }
}
