<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\AuthorizationEndpoint\Rule;

use Assert\Assertion;
use OAuth2Framework\Component\ClientRule\Rule;
use OAuth2Framework\Component\ClientRule\RuleHandler;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;

final class RequestUriRule implements Rule
{
    public function handle(ClientId $clientId, DataBag $commandParameters, DataBag $validatedParameters, RuleHandler $next): DataBag
    {
        $validatedParameters = $next->handle($clientId, $commandParameters, $validatedParameters);
        if (!$validatedParameters->has('response_types') || 0 === \count($validatedParameters->get('response_types'))) {
            return $validatedParameters;
        }
        if ($commandParameters->has('request_uris')) {
            $this->checkAllUris($commandParameters->get('request_uris'));
            $validatedParameters->set('request_uris', $commandParameters->get('request_uris'));
        }

        return $validatedParameters;
    }

    /**
     * @param mixed $value
     */
    private function checkAllUris($value): void
    {
        Assertion::isArray($value, 'The parameter "request_uris" must be a list of URI.');
        foreach ($value as $redirectUri) {
            Assertion::string($redirectUri, 'The parameter "request_uris" must be a list of URI.');
            Assertion::true(false !== filter_var($redirectUri, FILTER_VALIDATE_URL), 'The parameter "request_uris" must be a list of URI.'); //TODO: URN should be allowed
        }
    }
}
