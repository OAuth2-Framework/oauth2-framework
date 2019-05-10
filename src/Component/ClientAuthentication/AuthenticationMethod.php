<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\ClientAuthentication;

use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use Psr\Http\Message\ServerRequestInterface;

interface AuthenticationMethod
{
    /**
     * @return string[]
     */
    public function getSupportedMethods(): array;

    /**
     * Find a client using the request.
     * If the client is confidential, the client credentials must be checked.
     *
     * @param ServerRequestInterface $request           The request
     * @param mixed                  $clientCredentials The client credentials found in the request
     *
     * @return ClientId|null Return the client public ID if found else null. If credentials have are needed to authenticate the client, they are set to the variable $clientCredentials
     */
    public function findClientIdAndCredentials(ServerRequestInterface $request, &$clientCredentials = null): ?ClientId;

    public function checkClientConfiguration(DataBag $command_parameters, DataBag $validated_parameters): DataBag;

    /**
     * @param mixed $clientCredentials The client credentials found in the request
     */
    public function isClientAuthenticated(Client $client, $clientCredentials, ServerRequestInterface $request): bool;

    /**
     * @return string[]
     */
    public function getSchemesParameters(): array;
}
