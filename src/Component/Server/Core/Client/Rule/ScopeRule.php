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
use OAuth2Framework\Component\Server\Core\Scope\ScopeRepository;


final class ScopeRule implements Rule
{
    /**
     * @var ScopeRepository
     */
    private $scopeManager;

    /**
     * @param ScopeRepository $scopeManager
     */
    public function __construct(ScopeRepository $scopeManager)
    {
        $this->scopeManager = $scopeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ClientId $clientId, DataBag $commandParameters, DataBag $validatedParameters, callable $next): DataBag
    {
        if ($commandParameters->has('scope')) {
            $defaultScope = $commandParameters->get('scope');
            if (!is_string($defaultScope)) {
                throw new \InvalidArgumentException('The parameter "scope" must be a string.');
            }
            if (1 !== preg_match( '/^[\x20\x23-\x5B\x5D-\x7E]+$/', $defaultScope)) {
                throw new \InvalidArgumentException('Invalid characters found in the "scope" parameter.');
            }
            $validatedParameters = $validatedParameters->with('scope', $commandParameters->get('scope'));
        }

        return $next($clientId, $commandParameters, $validatedParameters);
    }
}
