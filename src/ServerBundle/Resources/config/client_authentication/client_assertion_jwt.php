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

use OAuth2Framework\Component\ClientAuthentication\ClientAssertionJwt;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set(ClientAssertionJwt::class)
        ->args([
            ref('jose.jws_verifier.client_authentication.client_assertion_jwt'),
            ref('jose.header_checker.client_authentication.client_assertion_jwt'),
            ref('jose.claim_checker.client_authentication.client_assertion_jwt'),
            '%oauth2_server.client_authentication.client_assertion_jwt.secret_lifetime%',
        ])
    ;
};
