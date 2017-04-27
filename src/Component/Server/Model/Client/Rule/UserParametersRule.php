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

namespace OAuth2Framework\Component\Server\Model\Client\Rule;

use Assert\Assertion;
use OAuth2Framework\Component\Server\Model\DataBag\DataBag;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;

final class UserParametersRule implements RuleInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle(DataBag $commandParameters, DataBag $validatedParameters, ? UserAccountId $userAccountId, callable $next): DataBag
    {
        if ($commandParameters->has('require_auth_time')) {
            $require_auth_time = $commandParameters->get('require_auth_time');
            Assertion::boolean($require_auth_time, 'The parameter \'require_auth_time\' must be a boolean.');
            $validatedParameters = $validatedParameters->with('require_auth_time', $require_auth_time);
        }
        if ($commandParameters->has('default_max_age')) {
            $default_max_age = $commandParameters->get('default_max_age');
            Assertion::integer($default_max_age, 'The parameter \'default_max_age\' must be a positive integer.');
            Assertion::min($default_max_age, 0, 'The parameter \'default_max_age\' must be a positive integer.');
            $validatedParameters = $validatedParameters->with('default_max_age', $default_max_age);
        }
        if ($commandParameters->has('default_acr_values')) {
            $default_acr_values = $commandParameters->get('default_acr_values');
            Assertion::isArray($default_acr_values, 'The parameter \'default_acr_values\' must be an array of strings.');
            Assertion::allString($default_acr_values, 'The parameter \'default_acr_values\' must be an array of strings.');
            $validatedParameters = $validatedParameters->with('default_acr_values', $default_acr_values);
        }

        return $next($commandParameters, $validatedParameters, $userAccountId);
    }
}
