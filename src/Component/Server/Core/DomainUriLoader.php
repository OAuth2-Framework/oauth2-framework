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

namespace OAuth2Framework\Component\Server\Core;

use League\JsonReference\SchemaLoadingException;
use League\JsonReference\LoaderInterface;

final class DomainUriLoader implements LoaderInterface
{
    /**
     * @var string[]
     */
    private $mappings;

    /**
     * DomainUriLoader constructor.
     */
    public function __construct()
    {
        /*
        $this->add('oauth2-framework.spomky-labs.com/schemas/model/pre-configured-authorization/1.0/schema', sprintf('file://%s%s', __DIR__, '/Model/PreConfiguredAuthorization/PreConfiguredAuthorization-1.0.json'));
        $this->add('oauth2-framework.spomky-labs.com/schemas/events/pre-configured-authorization/created/1.0/schema', sprintf('file://%s%s', __DIR__, '/Event/PreConfiguredAuthorization/PreConfiguredAuthorizationCreatedEvent-1.0.json'));
        $this->add('oauth2-framework.spomky-labs.com/schemas/events/pre-configured-authorization/revoked/1.0/schema', sprintf('file://%s%s', __DIR__, '/Event/PreConfiguredAuthorization/PreConfiguredAuthorizationRevokedEvent-1.0.json'));*/
    }

    /**
     * @param string $schema
     * @param string $filename
     */
    public function add(string $schema, string $filename)
    {
        $this->mappings[$schema] = $filename;
    }

    public function load($path)
    {
        if (array_key_exists($path, $this->mappings)) {
            $content = file_get_contents($this->mappings[$path]);

            return json_decode($content);
        }

        throw SchemaLoadingException::notFound(sprintf('The schema "%s" is not supported.', $path));
    }
}
