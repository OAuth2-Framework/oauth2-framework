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

namespace OAuth2Framework\Bundle\Server\DependencyInjection\Source\TokenType;

use Fluent\PhpConfigFileLoader;
use OAuth2Framework\Bundle\Server\DependencyInjection\Source\ArraySource;
use OAuth2Framework\Bundle\Server\DependencyInjection\Source\SourceInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class TokenTypeSource extends ArraySource
{
    /**
     * @var SourceInterface[]
     */
    private $tokenTypes = [];

    /**
     * TokenTypeSource constructor.
     */
    public function __construct()
    {
        $this->tokenTypes = [
            new BearerTokenTypeSource(),
            new MacTokenTypeSource(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(array $bundleConfig, string $path, ContainerBuilder $container)
    {
        foreach ($this->tokenTypes as $source) {
            $source->prepend($bundleConfig, $path.'['.$this->name().']', $container);
        }

        $loader = new PhpConfigFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/token_type'));
        $loader->load('token_type.php');
    }

    /**
     * @return string
     */
    protected function name(): string
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
        foreach ($this->tokenTypes as $source) {
            $source->load($path, $container, $config);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function continueConfiguration(NodeDefinition $node)
    {
        parent::continueConfiguration($node);
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
        foreach ($this->tokenTypes as $source) {
            $source->addConfiguration($node);
        }
    }
}
