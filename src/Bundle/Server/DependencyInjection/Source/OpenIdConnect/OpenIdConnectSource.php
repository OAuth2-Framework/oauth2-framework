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
use OAuth2Framework\Bundle\Server\DependencyInjection\Source\ActionableSource;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class OpenIdConnectSource extends ActionableSource
{
    /**
     * OpenIdConnectSource constructor.
     */
    public function __construct()
    {
        $this->addSubSource(new UserinfoEndpointSource());
        $this->addSubSource(new IdTokenSource());
        $this->addSubSource(new AuthorizationEndpointIdTokenHintSource());
        $this->addSubSource(new PairwiseSubjectSource());
    }

    /**
     * {@inheritdoc}
     */
    protected function name(): string
    {
        return 'openid_connect';
    }

    /**
     * {@inheritdoc}
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
        $loader = new PhpConfigFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/openid_connect'));
        $loader->load('openid_connect.php');
    }
}
