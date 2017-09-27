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

final class ClientIdRule implements RuleInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle(DataBag $commandParameters, DataBag $validatedParameters, ? UserAccountId $userAccountId, callable $next): DataBag
    {
        if (!$commandParameters->has('client_id')) {
            throw new \InvalidArgumentException('Client ID not defined.');
        }
        $validatedParameters = $validatedParameters->with('client_id', $commandParameters->get('client_id'));

        if ($commandParameters->has('client_id_issued_at')) {
            $validatedParameters = $validatedParameters->with('client_id_issued_at', $commandParameters->get('client_id_issued_at'));
        } else {
            $validatedParameters = $validatedParameters->with('client_id_issued_at', time());
        }

        return $next($commandParameters, $validatedParameters, $userAccountId);
    }

}
