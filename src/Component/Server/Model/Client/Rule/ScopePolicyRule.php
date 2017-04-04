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
use OAuth2Framework\Component\Server\Model\Scope\ScopePolicyManager;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;

final class ScopePolicyRule implements RuleInterface
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
    public function handle(DataBag $commandParameters, DataBag $validatedParameters, ?UserAccountId $userAccountId, callable $next): DataBag
    {
        if ($commandParameters->has('scope_policy')) {
            Assertion::true($this->scopePolicyManager->has($commandParameters->get('scope_policy')), sprintf('The scope policy \'%s\' is not supported.', $commandParameters->get('scope_policy')));
            $validatedParameters = $validatedParameters->with('scope_policy', $commandParameters->get('scope_policy'));
        }

        return $next($commandParameters, $validatedParameters, $userAccountId);
    }
}
