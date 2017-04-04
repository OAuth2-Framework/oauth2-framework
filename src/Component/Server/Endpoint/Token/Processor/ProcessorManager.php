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

namespace OAuth2Framework\Component\Server\Endpoint\Token\Processor;

use OAuth2Framework\Component\Server\Endpoint\Token\GrantTypeData;
use OAuth2Framework\Component\Server\GrantType\GrantTypeInterface;
use OAuth2Framework\Component\Server\Model\Scope\ScopePolicyManager;
use OAuth2Framework\Component\Server\Model\Scope\ScopeRepositoryInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ProcessorManager
{
    /**
     * @var array
     */
    private $processors = [];

    /**
     * ProcessorManager constructor.
     * @param null|ScopeRepositoryInterface $scopeRepository
     * @param null|ScopePolicyManager       $scopePolicyManager
     */
    public function __construct(?ScopeRepositoryInterface $scopeRepository, ?ScopePolicyManager $scopePolicyManager)
    {
        if (null !== $scopeRepository) {
            $this->processors[] = new ScopeProcessor($scopeRepository, $scopePolicyManager);
        }
        $this->processors[] = new TokenTypeProcessor();
    }

    /**
     * @param ServerRequestInterface $request
     * @param GrantTypeData          $grantTypeData
     * @param GrantTypeInterface     $grantType
     *
     * @return GrantTypeData
     */
    public function handle(ServerRequestInterface $request, GrantTypeData $grantTypeData, GrantTypeInterface $grantType): GrantTypeData
    {
        return call_user_func($this->callableForNextRule(0), $request, $grantTypeData, $grantType);
    }

    /**
     * @param int $index
     *
     * @return \Closure
     */
    private function callableForNextRule(int $index): \Closure
    {
        if (!isset($this->processors[$index])) {
            return function (ServerRequestInterface $request, GrantTypeData $grantTypeData, GrantTypeInterface $grantType): GrantTypeData {
                return $grantType->grant($request, $grantTypeData);
            };
        }
        $processor = $this->processors[$index];

        return function (ServerRequestInterface $request, GrantTypeData $grantTypeData, GrantTypeInterface $grantType) use ($processor, $index): GrantTypeData {
            return $processor($request, $grantTypeData, $grantType, $this->callableForNextRule($index + 1));
        };
    }
}
