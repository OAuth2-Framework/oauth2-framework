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

use OAuth2Framework\Component\Server\Model\DataBag\DataBag;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;

interface RuleInterface
{
    /**
     * @param DataBag            $commandParameters
     * @param DataBag            $validatedParameters
     * @param UserAccountId|null $userAccountId
     * @param callable           $next
     *
     * @return DataBag
     */
    public function handle(DataBag $commandParameters, DataBag $validatedParameters, ? UserAccountId $userAccountId, callable $next): DataBag;
}
