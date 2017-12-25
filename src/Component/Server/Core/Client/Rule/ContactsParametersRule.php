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

namespace OAuth2Framework\Component\Server\Core\Client\Rule;


use OAuth2Framework\Component\Server\Core\Client\ClientId;
use OAuth2Framework\Component\Server\Core\DataBag\DataBag;


final class ContactsParametersRule implements Rule
{
    /**
     * {@inheritdoc}
     */
    public function handle(ClientId $clientId, DataBag $commandParameters, DataBag $validatedParameters, callable $next): DataBag
    {
        if ($commandParameters->has('contacts')) {
            $contacts = $commandParameters->get('contacts');
            if (!is_array($contacts)) {
                throw new \InvalidArgumentException('The parameter "contacts" must be a list of e-mail addresses.');
            }
            foreach ($contacts as $contact) {
                if (!filter_var($contact, FILTER_VALIDATE_EMAIL)) {
                    throw new \InvalidArgumentException('The parameter "contacts" must be a list of e-mail addresses.');
                }
            }
            $validatedParameters = $validatedParameters->with('contacts', $contacts);
        }

        return $next($clientId, $commandParameters, $validatedParameters);
    }
}
