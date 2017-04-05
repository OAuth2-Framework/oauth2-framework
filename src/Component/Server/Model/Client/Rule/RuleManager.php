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

final class RuleManager
{
    /**
     * @var RuleInterface[]
     */
    private $rules = [];

    /**
     * @var ClientIdRuleInterface
     */
    private $clientIdRule;

    /**
     * RuleManager constructor.
     *
     * @param ClientIdRuleInterface $clientIdRule
     * @param array                 $rules
     */
    public function __construct(ClientIdRuleInterface $clientIdRule, array $rules = [])
    {
        $this->clientIdRule = $clientIdRule;
        foreach ($rules as $rule) {
            $this->add($rule);
        }
    }

    /**
     * Appends new middleware for this message bus. Should only be used at configuration time.
     *
     * @param RuleInterface $rule
     *
     * @return RuleManager
     */
    public function add(RuleInterface $rule): RuleManager
    {
        $this->rules[] = $rule;

        return $this;
    }

    /**
     * @return RuleInterface[]
     */
    public function all(): array
    {
        return $this->rules;
    }

    /**
     * @param DataBag            $commandParameters
     * @param UserAccountId|null $userAccountId
     *
     * @return DataBag
     */
    public function handle(DataBag $commandParameters, ? UserAccountId $userAccountId): DataBag
    {
        return call_user_func($this->callableForNextRule(0), $commandParameters, new DataBag(), $userAccountId);
    }

    /**
     * @param int $index
     *
     * @return \Closure
     */
    private function callableForNextRule(int $index): \Closure
    {
        if (!isset($this->rules[$index])) {
            return function (DataBag $commandParameters, DataBag $validatedParameters): DataBag {
                $clientId = $this->clientIdRule->generateUniqueClientId();
                $validatedParameters = $validatedParameters->with('client_id', $clientId);
                $validatedParameters = $validatedParameters->with('client_id_issued_at', time());

                return $validatedParameters;
            };
        }
        $rule = $this->rules[$index];

        return function (DataBag $commandParameters, DataBag $validatedParameters, ? UserAccountId $userAccountId) use ($rule, $index): DataBag {
            return $rule->handle($commandParameters, $validatedParameters, $userAccountId, $this->callableForNextRule($index + 1));
        };
    }
}
