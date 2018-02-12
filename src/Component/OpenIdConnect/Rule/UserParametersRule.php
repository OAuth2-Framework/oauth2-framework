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

namespace OAuth2Framework\Component\ClientRule;

use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;

class UserParametersRule implements Rule
{
    /**
     * {@inheritdoc}
     */
    public function handle(ClientId $clientId, DataBag $commandParameters, DataBag $validatedParameters, callable $next): DataBag
    {
        if ($commandParameters->has('require_auth_time')) {
            $require_auth_time = $commandParameters->get('require_auth_time');
            if (!is_bool($require_auth_time)) {
                throw new \InvalidArgumentException('The parameter "require_auth_time" must be a boolean.');
            }
            $validatedParameters = $validatedParameters->with('require_auth_time', $require_auth_time);
        }
        if ($commandParameters->has('default_max_age')) {
            $default_max_age = $commandParameters->get('default_max_age');
            if (!is_int($default_max_age) || $default_max_age < 0) {
                throw new \InvalidArgumentException('The parameter "default_max_age" must be a positive integer.');
            }
            $validatedParameters = $validatedParameters->with('default_max_age', $default_max_age);
        }
        if ($commandParameters->has('default_acr_values')) {
            $default_acr_values = $commandParameters->get('default_acr_values');
            if (!is_array($default_acr_values)) {
                throw new \InvalidArgumentException('The parameter "default_acr_values" must be an array of strings.');
            }
            array_map(function ($default_acr_value) {
                if (!is_string($default_acr_value)) {
                    throw new \InvalidArgumentException('The parameter "default_acr_values" must be an array of strings.');
                }
            }, $default_acr_values);
            $validatedParameters = $validatedParameters->with('default_acr_values', $default_acr_values);
        }

        return $next($clientId, $commandParameters, $validatedParameters);
    }
}
