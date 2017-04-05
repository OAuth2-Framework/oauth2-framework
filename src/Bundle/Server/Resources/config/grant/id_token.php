<?php

declare(strict_types = 1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

use OAuth2Framework\Component\Server\Endpoint\UserInfo\ClaimSource\ClaimSourceManager;
use OAuth2Framework\Component\Server\Endpoint\UserInfo\ScopeSupport\UserInfoScopeSupportManager;
use OAuth2Framework\Component\Server\Endpoint\UserInfo\UserInfo;
use OAuth2Framework\Component\Server\Model\IdToken\IdTokenBuilderFactory;
use OAuth2Framework\Component\Server\ResponseType\IdTokenResponseType;
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

    IdTokenResponseType::class => create()
        ->arguments(
            get('id_token_builder_factory_for_response_type'),
            '%oauth2_server.grant.id_token.default_signature_algorithm%'
        )
        ->tag('oauth2_server_response_type'),

    'id_token_builder_factory_for_response_type' => create(IdTokenBuilderFactory::class)
        ->arguments(
            get('jose.jwt_creator.id_token'),
            '%oauth2_server.server_uri%',
            get(UserInfo::class),
            get('oauth2_server.grant.id_token.key_set'),
            '%oauth2_server.grant.id_token.lifetime%'
        ),

    /*IdTokenEncryptionAlgorithmsRule::class => create()
        ->arguments(
            get(IdTokenRepository::class)
        )
        ->tag('oauth2_server_client_rule'),

    IdTokenHintDiscovery::class => create()
        ->arguments(
            get(IdTokenRepository::class),
            get('oauth2_server.user_account.repository')
        )
        ->tag('oauth2_server_user_account_discovery'),*/
];
