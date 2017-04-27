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

final class ContactsParametersRule implements RuleInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle(DataBag $commandParameters, DataBag $validatedParameters, ? UserAccountId $userAccountId, callable $next): DataBag
    {
        if ($commandParameters->has('contacts')) {
            $contacts = $commandParameters->get('contacts');
            Assertion::isArray($contacts, 'The parameter \'contacts\' must be a list of e-mail addresses.');
            Assertion::allEmail($contacts, 'The parameter \'contacts\' must be a list of e-mail addresses.');
            $validatedParameters = $validatedParameters->with('contacts', $contacts);
        }

        return $next($commandParameters, $validatedParameters, $userAccountId);
    }
}
