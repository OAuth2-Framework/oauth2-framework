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

use JsonSchema\UriRetrieverInterface;
use Webmozart\PathUtil\Path;

final class DomainUriRetriever implements UriRetrieverInterface
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
        $this->computePaths([
            'https://oauth2-framework.spomky-labs.com/schemas/model/token/1.0/schema' => 'Model/Token/Token-1.0.json',
            'https://oauth2-framework.spomky-labs.com/schemas/model/client/1.0/schema' => 'Model/Client/Client-1.0.json',
            'https://oauth2-framework.spomky-labs.com/schemas/model/access-token/1.0/schema' => 'Model/AccessToken/AccessToken-1.0.json',
            'https://oauth2-framework.spomky-labs.com/schemas/model/refresh-token/1.0/schema' => 'Model/RefreshToken/RefreshToken-1.0.json',
            'https://oauth2-framework.spomky-labs.com/schemas/model/auth-code/1.0/schema' => 'Model/AuthCode/AuthCode-1.0.json',
            'https://oauth2-framework.spomky-labs.com/schemas/model/pre-configured-authorization/1.0/schema' => 'Model/PreConfiguredAuthorization/PreConfiguredAuthorization-1.0.json',
            'https://oauth2-framework.spomky-labs.com/schemas/model/initial-access-token/1.0/schema' => 'Model/InitialAccessToken/InitialAccessToken-1.0.json',
            'https://oauth2-framework.spomky-labs.com/schemas/event/1.0/schema' => 'Event/Event-1.0.json',
            'https://oauth2-framework.spomky-labs.com/schemas/events/access-token/created/1.0/schema' => 'Event/AccessToken/AccessTokenCreatedEvent-1.0.json',
            'https://oauth2-framework.spomky-labs.com/schemas/events/access-token/revoked/1.0/schema' => 'Event/AccessToken/AccessTokenRevokedEvent-1.0.json',
            'https://oauth2-framework.spomky-labs.com/schemas/events/auth-code/created/1.0/schema' => 'Event/AuthCode/AuthCodeCreatedEvent-1.0.json',
            'https://oauth2-framework.spomky-labs.com/schemas/events/auth-code/marked-as-used/1.0/schema' => 'Event/AuthCode/AuthCodeMarkedAsUsedEvent-1.0.json',
            'https://oauth2-framework.spomky-labs.com/schemas/events/auth-code/revoked/1.0/schema' => 'Event/AuthCode/AuthCodeRevokedEvent-1.0.json',
            'https://oauth2-framework.spomky-labs.com/schemas/events/client/created/1.0/schema' => 'Event/Client/ClientCreatedEvent-1.0.json',
            'https://oauth2-framework.spomky-labs.com/schemas/events/client/deleted/1.0/schema' => 'Event/Client/ClientDeletedEvent-1.0.json',
            'https://oauth2-framework.spomky-labs.com/schemas/events/client/owner-changed/1.0/schema' => 'Event/Client/ClientOwnerChangedEvent-1.0.json',
            'https://oauth2-framework.spomky-labs.com/schemas/events/client/parameters-updated/1.0/schema' => 'Event/Client/ClientParametersUpdatedEvent-1.0.json',
            'https://oauth2-framework.spomky-labs.com/schemas/events/initial-access-token/created/1.0/schema' => 'Event/InitialAccessToken/InitialAccessTokenCreatedEvent-1.0.json',
            'https://oauth2-framework.spomky-labs.com/schemas/events/initial-access-token/revoked/1.0/schema' => 'Event/InitialAccessToken/InitialAccessTokenRevokedEvent-1.0.json',
            'https://oauth2-framework.spomky-labs.com/schemas/events/pre-configured-authorization/created/1.0/schema' => 'Event/PreConfiguredAuthorization/PreConfiguredAuthorizationCreatedEvent-1.0.json',
            'https://oauth2-framework.spomky-labs.com/schemas/events/pre-configured-authorization/revoked/1.0/schema' => 'Event/PreConfiguredAuthorization/PreConfiguredAuthorizationRevokedEvent-1.0.json',
            'https://oauth2-framework.spomky-labs.com/schemas/events/refresh-token/created/1.0/schema' => 'Event/RefreshToken/RefreshTokenCreatedEvent-1.0.json',
            'https://oauth2-framework.spomky-labs.com/schemas/events/refresh-token/access-token-added/1.0/schema' => 'Event/RefreshToken/AccessTokenAddedToRefreshTokenEvent-1.0.json',
            'https://oauth2-framework.spomky-labs.com/schemas/events/refresh-token/revoked/1.0/schema' => 'Event/RefreshToken/RefreshTokenRevokedEvent-1.0.json',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function retrieve($uri, $baseUri = null)
    {
        if (array_key_exists($uri, $this->mappings)) {
            $content = file_get_contents($this->mappings[$uri]);

            return json_decode($content);
        }
        throw new \RuntimeException(sprintf('The schema \'%s\' is not supported.', $uri));
    }

    /**
     * @param array $mappings
     */
    private function computePaths(array $mappings)
    {
        $base = Path::canonicalize(__DIR__);
        foreach ($mappings as $k => $p) {
            $uri = sprintf('file://%s', Path::makeAbsolute($p, $base));
            $this->mappings[$k] = $uri;
        }
    }
}
