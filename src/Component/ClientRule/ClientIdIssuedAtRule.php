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

final class ClientIdIssuedAtRule implements Rule
{
    public function handle(ClientId $clientId, DataBag $commandParameters, DataBag $validatedParameters, RuleHandler $next): DataBag
    {
        if ($commandParameters->has('client_id_issued_at')) {
            $validatedParameters->set('client_id_issued_at', $commandParameters->get('client_id_issued_at'));
        } else {
            $validatedParameters->set('client_id_issued_at', \time());
        }

        return $next->handle($clientId, $commandParameters, $validatedParameters);
    }
}
