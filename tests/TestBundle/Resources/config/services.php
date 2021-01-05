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

use OAuth2Framework\Component\BearerTokenType;
use OAuth2Framework\Component\BearerTokenType\BearerToken;
use OAuth2Framework\SecurityBundle\Tests\TestBundle\Controller\ApiController;
use OAuth2Framework\SecurityBundle\Tests\TestBundle\Service\AccessTokenHandler;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return static function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->public()
        ->autowire()
        ->autoconfigure()
    ;

    $container->set(ApiController::class);
    $container->set(AccessTokenHandler::class);

    $container->set('oauth2_security.bearer_token.authorization_header_token_finder')
        ->class(BearerTokenType\AuthorizationHeaderTokenFinder::class)
    ;

    $container->set('oauth2_security.bearer_token.query_string_token_finder')
        ->class(BearerTokenType\QueryStringTokenFinder::class)
    ;

    $container->set('oauth2_security.bearer_token.request_body_token_finder')
        ->class(BearerTokenType\RequestBodyTokenFinder::class)
    ;

    $container->set('oauth2_security.token_type.bearer_token')
        ->class(BearerToken::class)
        ->args(['Protected API'])
        ->tag('oauth2_security_token_type')
        ->call('addTokenFinder', [ref('oauth2_security.bearer_token.authorization_header_token_finder')])
        ->call('addTokenFinder', [ref('oauth2_security.bearer_token.query_string_token_finder')])
        ->call('addTokenFinder', [ref('oauth2_security.bearer_token.request_body_token_finder')])
    ;
};
