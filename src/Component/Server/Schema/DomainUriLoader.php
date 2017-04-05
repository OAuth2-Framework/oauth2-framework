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

namespace OAuth2Framework\Component\Server\Schema;

use League\JsonGuard\Exceptions\SchemaLoadingException;
use League\JsonGuard\Loader;

final class DomainUriLoader implements Loader
{
    /**
     * @var string[]
     */
    private $mappings;

    /**
     * EventUriRetriever constructor.
     */
    public function __construct()
    {
        $this->mappings = [
            'oauth2-framework.spomky-labs.com/schemas/model/token/1.0/schema' => sprintf('file://%s%s', __DIR__, '/Model/Token/Token-1.0.json'),
            'oauth2-framework.spomky-labs.com/schemas/model/client/1.0/schema' => sprintf('file://%s%s', __DIR__, '/Model/Client/Client-1.0.json'),
            'oauth2-framework.spomky-labs.com/schemas/model/access-token/1.0/schema' => sprintf('file://%s%s', __DIR__, '/Model/AccessToken/AccessToken-1.0.json'),
            'oauth2-framework.spomky-labs.com/schemas/model/refresh-token/1.0/schema' => sprintf('file://%s%s', __DIR__, '/Model/RefreshToken/RefreshToken-1.0.json'),
            'oauth2-framework.spomky-labs.com/schemas/model/auth-code/1.0/schema' => sprintf('file://%s%s', __DIR__, '/Model/AuthCode/AuthCode-1.0.json'),
            'oauth2-framework.spomky-labs.com/schemas/model/pre-configured-authorization/1.0/schema' => sprintf('file://%s%s', __DIR__, '/Model/PreConfiguredAuthorization/PreConfiguredAuthorization-1.0.json'),
            'oauth2-framework.spomky-labs.com/schemas/model/initial-access-token/1.0/schema' => sprintf('file://%s%s', __DIR__, '/Model/InitialAccessToken/InitialAccessToken-1.0.json'),
            'oauth2-framework.spomky-labs.com/schemas/event/1.0/schema' => sprintf('file://%s%s', __DIR__, '/Event/Event-1.0.json'),
            'oauth2-framework.spomky-labs.com/schemas/events/access-token/created/1.0/schema' => sprintf('file://%s%s', __DIR__, '/Event/AccessToken/AccessTokenCreatedEvent-1.0.json'),
            'oauth2-framework.spomky-labs.com/schemas/events/access-token/revoked/1.0/schema' => sprintf('file://%s%s', __DIR__, '/Event/AccessToken/AccessTokenRevokedEvent-1.0.json'),
            'oauth2-framework.spomky-labs.com/schemas/events/auth-code/created/1.0/schema' => sprintf('file://%s%s', __DIR__, '/Event/AuthCode/AuthCodeCreatedEvent-1.0.json'),
            'oauth2-framework.spomky-labs.com/schemas/events/auth-code/marked-as-used/1.0/schema' => sprintf('file://%s%s', __DIR__, '/Event/AuthCode/AuthCodeMarkedAsUsedEvent-1.0.json'),
            'oauth2-framework.spomky-labs.com/schemas/events/auth-code/revoked/1.0/schema' => sprintf('file://%s%s', __DIR__, '/Event/AuthCode/AuthCodeRevokedEvent-1.0.json'),
            'oauth2-framework.spomky-labs.com/schemas/events/client/created/1.0/schema' => sprintf('file://%s%s', __DIR__, '/Event/Client/ClientCreatedEvent-1.0.json'),
            'oauth2-framework.spomky-labs.com/schemas/events/client/deleted/1.0/schema' => sprintf('file://%s%s', __DIR__, '/Event/Client/ClientDeletedEvent-1.0.json'),
            'oauth2-framework.spomky-labs.com/schemas/events/client/owner-changed/1.0/schema' => sprintf('file://%s%s', __DIR__, '/Event/Client/ClientOwnerChangedEvent-1.0.json'),
            'oauth2-framework.spomky-labs.com/schemas/events/client/parameters-updated/1.0/schema' => sprintf('file://%s%s', __DIR__, '/Event/Client/ClientParametersUpdatedEvent-1.0.json'),
            'oauth2-framework.spomky-labs.com/schemas/events/initial-access-token/created/1.0/schema' => sprintf('file://%s%s', __DIR__, '/Event/InitialAccessToken/InitialAccessTokenCreatedEvent-1.0.json'),
            'oauth2-framework.spomky-labs.com/schemas/events/initial-access-token/revoked/1.0/schema' => sprintf('file://%s%s', __DIR__, '/Event/InitialAccessToken/InitialAccessTokenRevokedEvent-1.0.json'),
            'oauth2-framework.spomky-labs.com/schemas/events/pre-configured-authorization/created/1.0/schema' => sprintf('file://%s%s', __DIR__, '/Event/PreConfiguredAuthorization/PreConfiguredAuthorizationCreatedEvent-1.0.json'),
            'oauth2-framework.spomky-labs.com/schemas/events/pre-configured-authorization/revoked/1.0/schema' => sprintf('file://%s%s', __DIR__, '/Event/PreConfiguredAuthorization/PreConfiguredAuthorizationRevokedEvent-1.0.json'),
            'oauth2-framework.spomky-labs.com/schemas/events/refresh-token/created/1.0/schema' => sprintf('file://%s%s', __DIR__, '/Event/RefreshToken/RefreshTokenCreatedEvent-1.0.json'),
            'oauth2-framework.spomky-labs.com/schemas/events/refresh-token/access-token-added/1.0/schema' => sprintf('file://%s%s', __DIR__, '/Event/RefreshToken/AccessTokenAddedToRefreshTokenEvent-1.0.json'),
            'oauth2-framework.spomky-labs.com/schemas/events/refresh-token/revoked/1.0/schema' => sprintf('file://%s%s', __DIR__, '/Event/RefreshToken/RefreshTokenRevokedEvent-1.0.json'),
        ];
    }

    public function load($path)
    {
        if (array_key_exists($path, $this->mappings)) {
            $content = file_get_contents($this->mappings[$path]);

            return json_decode($content);
        }
        throw SchemaLoadingException::notFound(sprintf('The schema \'%s\' is not supported.', $path));
    }
}
