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

use function Fluent\create;
use function Fluent\get;

return [
    Rule\UserParametersRule::class => create()
        ->tag('oauth2_server_client_rule'),

    Rule\ApplicationTypeParametersRule::class => create()
        ->tag('oauth2_server_client_rule'),

    Rule\ContactsParametersRule::class => create()
        ->tag('oauth2_server_client_rule'),

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

    Rule\ClientIdRule::class => create()
        ->tag('oauth2_server_client_rule'),

    Rule\RequestUriRule::class => create()
        ->tag('oauth2_server_client_rule'),

    Rule\SectorIdentifierUriRule::class => create()
        ->arguments(
            get('httplug.message_factory'),
            get('oauth2_server.http.client')
        )
        ->tag('oauth2_server_client_rule'),

    Rule\TokenEndpointAuthMethodEndpointRule::class => create()
        ->arguments(
            get(TokenEndpointAuthMethodManager::class)
        )
        ->tag('oauth2_server_client_rule'),
];
