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

class ScopePolicyDefaultRule implements Rule
{
    /**
     * {@inheritdoc}
     */
    public function handle(ClientId $clientId, DataBag $commandParameters, DataBag $validatedParameters, callable $next): DataBag
    {
        if ($commandParameters->has('default_scope')) {
            $defaultScope = $commandParameters->get('default_scope');
            if (!is_string($defaultScope)) {
                throw new \InvalidArgumentException('The "default_scope" parameter must be a string.');
            }
            if (1 !== preg_match('/^[\x20\x23-\x5B\x5D-\x7E]+$/', $defaultScope)) {
                throw new \InvalidArgumentException('Invalid characters found in the "default_scope" parameter.');
            }
            $validatedParameters = $validatedParameters->with('default_scope', $commandParameters->get('default_scope'));
        }

        return $next($clientId, $commandParameters, $validatedParameters);
    }
}
