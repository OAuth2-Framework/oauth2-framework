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

class ClientIdIssuedAtRule implements Rule
{
    /**
     * {@inheritdoc}
     */
    public function handle(ClientId $clientId, DataBag $commandParameters, DataBag $validatedParameters, callable $next): DataBag
    {
        if ($commandParameters->has('client_id_issued_at')) {
            $validatedParameters = $validatedParameters->with('client_id_issued_at', $commandParameters->get('client_id_issued_at'));
        } else {
            $validatedParameters = $validatedParameters->with('client_id_issued_at', time());
        }

        return $next($clientId, $commandParameters, $validatedParameters);
    }
}
