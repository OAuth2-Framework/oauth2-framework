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

use OAuth2Framework\Component\Server\Model\Client\Rule\UserinfoEndpointAlgorithmsRule;
use OAuth2Framework\Bundle\Server\Model\ClientRepository;
use OAuth2Framework\Component\Server\Endpoint\UserInfo\UserInfoEndpoint;
use OAuth2Framework\Component\Server\Middleware\OAuth2ResponseMiddleware;
use OAuth2Framework\Component\Server\Middleware\OAuth2SecurityMiddleware;
use OAuth2Framework\Component\Server\Middleware\Pipe;
use OAuth2Framework\Component\Server\Model\IdToken\IdTokenBuilderFactory;
use OAuth2Framework\Component\Server\Security\AccessTokenHandlerManager;
use OAuth2Framework\Component\Server\TokenType\TokenTypeManager;
use function Fluent\create;
use function Fluent\get;
use OAuth2Framework\Component\Server\Middleware\FormPostBodyParserMiddleware;

return [
    UserInfoEndpoint::class => create()
        ->arguments(
            get(IdTokenBuilderFactory::class),
            get(ClientRepository::class),
            get('oauth2_server.user_account.repository'),
            get('oauth2_server.http.response_factory'),
            get('jose.signer.id_token')->nullIfMissing(),
            get('oauth2_server.openid_connect.id_token.key_set')->nullIfMissing(),
            get('jose.encrypter.id_token')->nullIfMissing()
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
            get(FormPostBodyParserMiddleware::class),
            get('userinfo_security_middleware'),
            get(UserInfoEndpoint::class),
        ]),

    UserinfoEndpointAlgorithmsRule::class => create()
        ->arguments(
            get('jose.signer.id_token')->nullIfMissing(),
            get('oauth2_server.endpoint.id_token.signature.key_set')->nullIfMissing(),
            get('jose.encrypter.id_token')->nullIfMissing()
        )
        ->tag('oauth2_server_client_rule'),
];
