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

namespace OAuth2Framework\Bundle\DependencyInjection\Component\TokenType;

use Fluent\PhpConfigFileLoader;
use OAuth2Framework\Bundle\DependencyInjection\Component\Component;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class TokenTypeSource implements Component
{
    /**
     * TokenTypeSource constructor.
     */
    public function __construct()
    {
        $this->addSubSource(new BearerTokenTypeSource());
        $this->addSubSource(new MacTokenTypeSource());
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return 'token_type';
    }

    /**
     * {@inheritdoc}
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
        foreach ($config as $k => $v) {
            $container->setParameter($path.'.'.$k, $v);
        }
        $loader = new PhpConfigFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/token_type'));
        $loader->load('token_type.php');
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(NodeDefinition $node)
    {
        $node
            ->children()
                ->scalarNode('default')
                    ->isRequired()
                    ->info('The default token type used for access token issuance.')
                ->end()
                ->booleanNode('allow_token_type_parameter')
                    ->defaultFalse()
                    ->info('If true, the "token_type" parameter will be allowed in requests.')
                ->end()
            ->end();
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container, array $config): array
    {
        //Nothing to do
        return [];
    }
}