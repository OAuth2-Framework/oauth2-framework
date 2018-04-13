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

namespace OAuth2Framework\Component\ClientConfigurationEndpoint\Rule;

use OAuth2Framework\Component\ClientRule\Rule;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;

abstract class ClientConfigurationRouteRule implements Rule
{
    /**
     * {@inheritdoc}
     */
    public function handle(ClientId $clientId, DataBag $commandParameters, DataBag $validatedParameters, callable $next): DataBag
    {
        $validatedParameters = $validatedParameters->with('registration_access_token', $this->generateRegistrationAccessToken());
        $validatedParameters = $validatedParameters->with('registration_client_uri', $this->getRegistrationClientUri($clientId));

        return $next($clientId, $commandParameters, $validatedParameters);
    }

    /**
     * @param ClientId $clientId
     *
     * @return string
     */
    abstract protected function getRegistrationClientUri(ClientId $clientId): string;

    /**
     * @return string
     */
    abstract protected function generateRegistrationAccessToken(): string;
}
