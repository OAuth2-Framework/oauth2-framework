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

final class RequestUriRule implements RuleInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle(DataBag $commandParameters, DataBag $validatedParameters, ? UserAccountId $userAccountId, callable $next): DataBag
    {
        if ($commandParameters->has('request_uris')) {
            Assertion::isArray($commandParameters->get('request_uris'), 'The parameter \'request_uris\' must be a list of URI.');
            Assertion::allUrl($commandParameters->get('request_uris'), 'The parameter \'request_uris\' must be a list of URI.');
            $validatedParameters = $validatedParameters->with('request_uris', $commandParameters->get('request_uris'));
        }

        return $next($commandParameters, $validatedParameters, $userAccountId);
    }
}
