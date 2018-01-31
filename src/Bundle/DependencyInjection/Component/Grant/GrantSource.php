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

namespace OAuth2Framework\Bundle\DependencyInjection\Component\Grant;

use Fluent\PhpConfigFileLoader;
use OAuth2Framework\Bundle\DependencyInjection\Component\Component;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class GrantSource implements Component
{
    /**
     * TokenEndpointAuthMethodSource constructor.
     */
    public function __construct()
    {
        $this->addSubSource(new AuthorizationCodeSource());
        $this->addSubSource(new ClientCredentialsSource());
        $this->addSubSource(new ImplicitSource());
        $this->addSubSource(new NoneSource());
        $this->addSubSource(new ResourceOwnerPasswordCredentialSource());
        $this->addSubSource(new JwtBearerSource());
        $this->addSubSource(new RefreshTokenSource());
    }

    /**
     * {@inheritdoc}
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
        $loader = new PhpConfigFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/grant'));
        $loader->load('grant.php');
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'grant';
    }
}
