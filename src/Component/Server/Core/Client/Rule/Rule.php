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

interface Rule
{
    /**
     * @param ClientId           $clientId
     * @param DataBag            $commandParameters
     * @param DataBag            $validatedParameters
     * @param UserAccountId|null $userAccountId
     * @param callable           $next
     *
     * @return DataBag
     */
    public function handle(ClientId $clientId, DataBag $commandParameters, DataBag $validatedParameters, callable $next): DataBag;
}