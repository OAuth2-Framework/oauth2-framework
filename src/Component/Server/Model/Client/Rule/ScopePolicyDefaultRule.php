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

final class ScopePolicyDefaultRule implements RuleInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle(DataBag $commandParameters, DataBag $validatedParameters, ? UserAccountId $userAccountId, callable $next): DataBag
    {
        if ($commandParameters->has('default_scope')) {
            Assertion::regex($commandParameters->get('default_scope'), '/^[\x20\x23-\x5B\x5D-\x7E]+$/', 'Invalid characters found in the \'default_scope\' parameter.');
            $validatedParameters = $validatedParameters->with('default_scope', $commandParameters->get('default_scope'));
        }

        return $next($commandParameters, $validatedParameters, $userAccountId);
    }
}
