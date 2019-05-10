<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\ClientRule;

use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;

final class ContactsParametersRule implements Rule
{
    public function handle(ClientId $clientId, DataBag $commandParameters, DataBag $validatedParameters, RuleHandler $next): DataBag
    {
        if ($commandParameters->has('contacts')) {
            $contacts = $commandParameters->get('contacts');
            if (!\is_array($contacts)) {
                throw new \InvalidArgumentException('The parameter "contacts" must be a list of e-mail addresses.');
            }
            \array_map(function ($contact) {
                if (false === \filter_var($contact, FILTER_VALIDATE_EMAIL)) {
                    throw new \InvalidArgumentException('The parameter "contacts" must be a list of e-mail addresses.');
                }
            }, $contacts);
            $validatedParameters->set('contacts', $contacts);
        }

        return $next->handle($clientId, $commandParameters, $validatedParameters);
    }
}
