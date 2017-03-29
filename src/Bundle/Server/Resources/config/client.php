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

use OAuth2Framework\Component\Server\Command\Client;
use OAuth2Framework\Component\Server\Endpoint\UserInfo\UserInfo;
use OAuth2Framework\Component\Server\GrantType\GrantTypeManager;
use OAuth2Framework\Component\Server\Model\Client\Rule;
use OAuth2Framework\Component\Server\Model\Client\Rule\RuleManager;
use OAuth2Framework\Component\Server\ResponseType\ResponseTypeManager;
use OAuth2Framework\Component\Server\TokenEndpointAuthMethod\TokenEndpointAuthMethodManager;
use OAuth2Framework\Bundle\Server\Rule\ClientIdRule;
use function Fluent\create;
use function Fluent\get;
use OAuth2Framework\Bundle\Server\Model\ClientRepository;

return [
    /*'oauth2_server.event_store.client' => create(OAuth2Framework\Bundle\Server\EventStore\EventStore::class)
        ->arguments(
            get('cache.app')
        ),*/
    'oauth2_server.event_store.client' => create(\OAuth2Framework\Bundle\Server\Tests\TestBundle\Service\EventStore::class)
        ->arguments(
            '%kernel.cache_dir%',
            'client'
        ),

    ClientRepository::class => create()
        ->arguments(
            get('oauth2_server.event_store.client'),
            get('event_recorder')
        ),

    RuleManager::class => create()
        ->arguments(
            get(ClientIdRule::class)
        ),

    ClientIdRule::class => create(),

    Rule\CommonParametersRule::class => create()
        ->tag('oauth2_server_client_rule'),

    Rule\GrantTypeFlowRule::class => create()
        ->arguments(
            get(GrantTypeManager::class),
            get(ResponseTypeManager::class)
        )
        ->tag('oauth2_server_client_rule'),

    Rule\RedirectionUriRule::class => create()
        ->tag('oauth2_server_client_rule'),

    Rule\RequestUriRule::class => create()
        ->tag('oauth2_server_client_rule'),

/*    Rule\SectorIdentifierUriRule::class => create()
        ->arguments(
            get(RequestFactoryInterface::class),
            get(JsonDecoder::class),
            get(HttpClient::class)
        )
        ->tag('oauth2_server_client_rule'),*/

    Rule\SubjectTypeRule::class => create()
        ->arguments(
            get(UserInfo::class)
        )
        ->tag('oauth2_server_client_rule'),

    Rule\TokenEndpointAuthMethodEndpointRule::class => create()
        ->arguments(
            get(TokenEndpointAuthMethodManager::class)
        )
        ->tag('oauth2_server_client_rule'),

    Client\CreateClientCommandHandler::class => create()
        ->arguments(
            get(ClientRepository::class),
            get(RuleManager::class)
        )
        ->tag('command_handler', ['handles' => Client\CreateClientCommand::class]),

    Client\DeleteClientCommandHandler::class => create()
        ->arguments(
            get(ClientRepository::class)
        )
        ->tag('command_handler', ['handles' => Client\DeleteClientCommand::class]),

    Client\UpdateClientCommandHandler::class => create()
        ->arguments(
            get(ClientRepository::class),
            get(RuleManager::class)
        )
        ->tag('command_handler', ['handles' => Client\UpdateClientCommand::class]),
];
