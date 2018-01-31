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

namespace OAuth2Framework\Bundle\DependencyInjection\Component\TokenEndpointAuthMethod;

use Fluent\PhpConfigFileLoader;
use OAuth2Framework\Bundle\DependencyInjection\Component\Component;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class TokenEndpointAuthMethodSource implements Component
{
    /**
     * TokenEndpointAuthMethodSource constructor.
     */
    public function __construct()
    {
        $this->addSubSource(new ClientAssertionJwtTokenEndpointAuthMethodSource());
        $this->addSubSource(new ClientSecretBasicTokenEndpointAuthMethodSource());
        $this->addSubSource(new ClientSecretPostTokenEndpointAuthMethodSource());
        $this->addSubSource(new NoneTokenEndpointAuthMethodSource());
    }

    /**
     * {@inheritdoc}
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
        $loader = new PhpConfigFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/token_endpoint_auth_method'));
        $loader->load('token_endpoint_auth_method.php');
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return 'token_endpoint_auth_method';
    }
}
