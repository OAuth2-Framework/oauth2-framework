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

namespace OAuth2Framework\Component\AuthorizationEndpoint\Rule;

use OAuth2Framework\Component\ClientRule\Rule;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;

class RequestUriRule implements Rule
{
    /**
     * {@inheritdoc}
     */
    public function handle(ClientId $clientId, DataBag $commandParameters, DataBag $validatedParameters, callable $next): DataBag
    {
        $validatedParameters = $next($clientId, $commandParameters, $validatedParameters);
        if (!$validatedParameters->has('response_types') || empty($validatedParameters->get('response_types'))) {
            return $validatedParameters;
        }
        if ($commandParameters->has('request_uris')) {
            $this->checkAllUris($commandParameters->get('request_uris'));
            $validatedParameters = $validatedParameters->with('request_uris', $commandParameters->get('request_uris'));
        }

        return $validatedParameters;
    }

    /**
     * @param mixed $value
     */
    private function checkAllUris($value)
    {
        if (!is_array($value)) {
            throw new \InvalidArgumentException('The parameter "request_uris" must be a list of URI.');
        }
        foreach ($value as $redirectUri) {
            if (!is_string($redirectUri) || !filter_var($redirectUri, FILTER_VALIDATE_URL)) {
                throw new \InvalidArgumentException('The parameter "request_uris" must be a list of URI.');
            }
        }
    }
}
