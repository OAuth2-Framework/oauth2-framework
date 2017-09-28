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

use OAuth2Framework\Component\Server\Model\Client\Rule;
use OAuth2Framework\Component\Server\Endpoint\Token\Extension\OpenIdConnectExtension;
use OAuth2Framework\Component\Server\Endpoint\UserInfo\ClaimSource\ClaimSourceManager;
use OAuth2Framework\Component\Server\Endpoint\UserInfo\ScopeSupport\UserInfoScopeSupportManager;
use OAuth2Framework\Component\Server\Endpoint\UserInfo\UserInfo;
use OAuth2Framework\Component\Server\Model\IdToken\IdTokenBuilderFactory;
use function Fluent\autowire;
use function Fluent\create;
use function Fluent\get;

return [
    UserInfoScopeSupportManager::class => create(),
    ClaimSourceManager::class => create(),

    UserInfo::class => autowire(),

    OpenIdConnectExtension::class => create()
        ->arguments(
            get(IdTokenBuilderFactory::class),
            '%oauth2_server.openid_connect.id_token.default_signature_algorithm%',
            get('jose.signer.id_token'),
            get('oauth2_server.openid_connect.id_token.key_set'),
            get('jose.encrypter.id_token')->nullIfMissing()
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
            get('jose.signer.id_token'),
            get('jose.encrypter.id_token')->nullIfMissing()
        )
        ->tag('oauth2_server_client_rule'),

    Rule\SubjectTypeRule::class => autowire()
        ->tag('oauth2_server_client_rule'),
];
