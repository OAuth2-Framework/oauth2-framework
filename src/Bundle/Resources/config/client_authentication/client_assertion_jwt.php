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

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use OAuth2Framework\Component\ClientAuthentication\ClientAssertionJwt;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure();

    $container->set(ClientAssertionJwt::class)
        ->args([
            ref('jose.jws_loader.client_assertion_jwt'),
            ref('jose.claim_checker.client_assertion_jwt'),
            '%oauth2_server.client_authentication.client_assertion_jwt.secret_lifetime%',
        ])
        ->tag('oauth2_server_client_authentication');
};
