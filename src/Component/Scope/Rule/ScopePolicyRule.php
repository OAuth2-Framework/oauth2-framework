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

namespace OAuth2Framework\Component\Scope\Rule;

use OAuth2Framework\Component\ClientRule\Rule;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Scope\Policy\ScopePolicyManager;

class ScopePolicyRule implements Rule
{
    /**
     * @var ScopePolicyManager
     */
    private $scopePolicyManager;

    /**
     * @param ScopePolicyManager $scopePolicyManager
     */
    public function __construct(ScopePolicyManager $scopePolicyManager)
    {
        $this->scopePolicyManager = $scopePolicyManager;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ClientId $clientId, DataBag $commandParameters, DataBag $validatedParameters, callable $next): DataBag
    {
        if ($commandParameters->has('scope_policy')) {
            $policy = $commandParameters->get('scope_policy');
            if (!is_string($policy)) {
                throw new \InvalidArgumentException('The parameter "scope_policy" must be a string.');
            }
            if (!$this->scopePolicyManager->has($policy)) {
                throw new \InvalidArgumentException(sprintf('The scope policy "%s" is not supported.', $policy));
            }
            $validatedParameters = $validatedParameters->with('scope_policy', $commandParameters->get('scope_policy'));
        }

        return $next($clientId, $commandParameters, $validatedParameters);
    }
}
