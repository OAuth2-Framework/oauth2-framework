<?php

declare(strict_types=1);

use OAuth2Framework\Component\BearerTokenType\BearerToken;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenRepository;
use OAuth2Framework\Component\Core\Client\ClientRepository;
/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

use OAuth2Framework\Component\Core\Middleware\AccessTokenMiddleware;
use OAuth2Framework\Component\Core\Middleware\Pipe;
use OAuth2Framework\Component\Core\TokenType\TokenTypeManager;
use OAuth2Framework\Component\Core\UserAccount\UserAccountRepository;
use OAuth2Framework\Component\OpenIdConnect\IdTokenBuilderFactory;
use OAuth2Framework\Component\OpenIdConnect\Rule\UserinfoEndpointAlgorithmsRule;
use OAuth2Framework\Component\OpenIdConnect\UserInfoEndpoint\UserInfoEndpoint;
use OAuth2Framework\ServerBundle\Controller\PipeController;
use Psr\Http\Message\ResponseFactoryInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set(UserInfoEndpoint::class)
        ->args([
            service(IdTokenBuilderFactory::class),
            service(ClientRepository::class),
            service(UserAccountRepository::class),
            service(ResponseFactoryInterface::class),
        ])
    ;

    $container->set('oauth2_server.userinfo_security.bearer_token_type')
        ->class(BearerToken::class)
        ->args([
            'Realm', //FIXME
            true,
            true,
            false,
        ])
    ;

    $container->set('oauth2_server.userinfo_security.token_type_manager')
        ->class(TokenTypeManager::class)
        ->call('add', [service('oauth2_server.userinfo_security.bearer_token_type')])
    ;

    $container->set('userinfo_security_middleware')
        ->class(AccessTokenMiddleware::class)
        ->args([service('oauth2_server.userinfo_security.token_type_manager'), service(AccessTokenRepository::class)])
    ;

    $container->set('oauth2_server_userinfo_pipe')
        ->class(Pipe::class)
        ->args([[service('userinfo_security_middleware'), service(UserInfoEndpoint::class)]])
    ;

    $container->set('oauth2_server_userinfo_endpoint_pipe')
        ->class(PipeController::class)
        ->args([service('oauth2_server_userinfo_pipe')])
        ->tag('controller.service_arguments')
    ;

    $container->set(UserinfoEndpointAlgorithmsRule::class)
        ->args([
            service('jose.jws_builder.oauth2_server.openid_connect.id_token_from_userinfo')
                ->nullOnInvalid(),
            service('jose.jwe_builder.oauth2_server.openid_connect.id_token_from_userinfo')
                ->nullOnInvalid(),
        ])
    ;
};
