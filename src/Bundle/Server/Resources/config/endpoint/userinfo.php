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

use Interop\Http\Factory\ResponseFactoryInterface;
use OAuth2Framework\Bundle\Server\Model\ClientRepository;
use OAuth2Framework\Component\Server\Endpoint\UserInfo\UserInfo;
use OAuth2Framework\Component\Server\Endpoint\UserInfo\UserInfoEndpoint;
use OAuth2Framework\Component\Server\Middleware\OAuth2ResponseMiddleware;
use OAuth2Framework\Component\Server\Middleware\OAuth2SecurityMiddleware;
use OAuth2Framework\Component\Server\Middleware\Pipe;
use OAuth2Framework\Component\Server\Model\IdToken\IdTokenBuilderFactory;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountRepositoryInterface;
use OAuth2Framework\Component\Server\Security\AccessTokenHandlerManager;
use OAuth2Framework\Component\Server\TokenType\TokenTypeManager;
use function Fluent\create;
use function Fluent\get;

return [
    UserInfoEndpoint::class => create()
        ->arguments(
            get('id_token_builder_factory_for_userinfo_endpoint'),
            get(ClientRepository::class),
            get(UserAccountRepositoryInterface::class),
            get(ResponseFactoryInterface::class)
        ),

    'userinfo_security_middleware' => create(OAuth2SecurityMiddleware::class)
        ->arguments(
            get(TokenTypeManager::class),
            get(AccessTokenHandlerManager::class),
            'openid', //Scope,
            [] // Additional Data
        ),

    'oauth2_server_userinfo_pipe' => create(Pipe::class)
        ->arguments([
            get(OAuth2ResponseMiddleware::class),
            get('userinfo_security_middleware'),
            get(UserInfoEndpoint::class),
        ]),

    'id_token_builder_factory_for_userinfo_endpoint' => create(IdTokenBuilderFactory::class)
    ->arguments(
        get('jose.jwt_creator.userinfo_endpoint'),
        '%oauth2_server.server_uri%',
        get(UserInfo::class),
        get('oauth2_server.endpoint.userinfo.signature.key_set'),
        0 // This lifetime will never be used as the ID Token will expire at the same time than the access token
    ),
];
