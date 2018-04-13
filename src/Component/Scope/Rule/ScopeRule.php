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

namespace OAuth2Framework\Component\Scope\Rule;

use OAuth2Framework\Component\ClientRule\Rule;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;

class ScopeRule implements Rule
{
    /**
     * {@inheritdoc}
     */
    public function handle(ClientId $clientId, DataBag $commandParameters, DataBag $validatedParameters, callable $next): DataBag
    {
        if ($commandParameters->has('scope')) {
            $scope = $commandParameters->get('scope');
            if (!is_string($scope)) {
                throw new \InvalidArgumentException('The "scope" parameter must be a string.');
            }
            if (1 !== preg_match('/^[\x20\x23-\x5B\x5D-\x7E]+$/', $scope)) {
                throw new \InvalidArgumentException('Invalid characters found in the "scope" parameter.');
            }
            $validatedParameters = $validatedParameters->with('scope', $scope);
        }

        return $next($clientId, $commandParameters, $validatedParameters);
    }
}
