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

final class ApplicationTypeParametersRule implements RuleInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle(DataBag $commandParameters, DataBag $validatedParameters, ? UserAccountId $userAccountId, callable $next): DataBag
    {
        if ($commandParameters->has('application_type')) {
            $application_type = $commandParameters->get('application_type');
            Assertion::inArray($application_type, ['native', 'web'], 'The parameter \'application_type\' must be either \'native\' or \'web\'.');
            $validatedParameters = $validatedParameters->with('application_type', $application_type);
        } else {
            $validatedParameters = $validatedParameters->with('application_type', 'web');
        }

        return $next($commandParameters, $validatedParameters, $userAccountId);
    }
}
