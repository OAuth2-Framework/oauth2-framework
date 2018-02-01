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

use OAuth2Framework\Component\BearerTokenType\BearerToken;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure()
        ->autowire();

    $container->set(BearerToken::class)
        ->args([
            '%oauth2_server.token_type.bearer_token.realm%',
            '%oauth2_server.token_type.bearer_token.authorization_header%',
            '%oauth2_server.token_type.bearer_token.request_body%',
            '%oauth2_server.token_type.bearer_token.query_string%',
        ])
        ->tag('oauth2_server_token_type', ['scheme' => 'Bearer']);
};
