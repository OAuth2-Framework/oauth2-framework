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

use OAuth2Framework\Component\BearerTokenType\AuthorizationHeaderTokenFinder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autowire()
        ->autoconfigure();

    $container->set('oauth2_security.bearer_token.authorization_header_token_finder')
        ->class(AuthorizationHeaderTokenFinder::class)
        ->tag('oauth2_security_bearer_token_finder');
};