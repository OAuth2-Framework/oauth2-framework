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

use Jose\Component\Core\Converter\JsonConverter;
use OAuth2Framework\Component\JwtBearerGrant\JwtBearerGrantType;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure();

    $container->set(JwtBearerGrantType::class)
        ->args([
            ref(JsonConverter::class),
            ref('jose.jws_verifier.oauth2_server.grant.jwt_bearer'),
            ref('jose.header_checker.oauth2_server.grant.jwt_bearer'),
            ref('jose.claim_checker.oauth2_server.grant.jwt_bearer'),
            ref('oauth2_server.client.repository'),
            ref(\OAuth2Framework\Component\Core\UserAccount\UserAccountRepository::class),
        ]);
};
