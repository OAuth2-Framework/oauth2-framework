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

use OAuth2Framework\Component\Server\Model\Client\Rule;
use OAuth2Framework\Component\Server\Endpoint\Token\Extension\OpenIdConnectExtension;
use OAuth2Framework\Component\Server\Endpoint\UserInfo\ClaimSource\ClaimSourceManager;
use OAuth2Framework\Component\Server\Endpoint\UserInfo\ScopeSupport\UserInfoScopeSupportManager;
use OAuth2Framework\Component\Server\Endpoint\UserInfo\UserInfo;
use OAuth2Framework\Component\Server\Model\IdToken\IdTokenBuilderFactory;
use function Fluent\create;
use function Fluent\get;

return [
    UserInfoScopeSupportManager::class => create(),
    ClaimSourceManager::class => create(),

    UserInfo::class => create()
        ->arguments(
            get(UserInfoScopeSupportManager::class),
            get(ClaimSourceManager::class)
        ),

    OpenIdConnectExtension::class => create()
        ->arguments(
            get(IdTokenBuilderFactory::class),
            '%oauth2_server.openid_connect.id_token.default_signature_algorithm%',
            get('jose.jws_builder.id_token'),
            get('jose.key_set.oauth2_server.key_set.signature'),
            get('jose.jwe_builder.id_token')->nullIfMissing()
        )
        ->tag('oauth2_server_token_endpoint_extension'),

    IdTokenBuilderFactory::class => create()
        ->arguments(
            '%oauth2_server.server_uri%',
            get(UserInfo::class),
            '%oauth2_server.openid_connect.id_token.lifetime%'
        ),

    Rule\IdTokenAlgorithmsRule::class => create()
        ->arguments(
            get('jose.jws_builder.id_token'),
            get('jose.jwe_builder.id_token')->nullIfMissing()
        )
        ->tag('oauth2_server_client_rule'),

    Rule\SubjectTypeRule::class => create()
        ->arguments(
            get(UserInfo::class)
        )
        ->tag('oauth2_server_client_rule'),
];
