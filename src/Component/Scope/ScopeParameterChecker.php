<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Scope;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\ParameterChecker;
use OAuth2Framework\Component\Scope\Policy\ScopePolicyManager;
use function Safe\preg_match;
use function Safe\sprintf;

class ScopeParameterChecker implements ParameterChecker
{
    /**
     * @var ScopeRepository
     */
    private $scopeRepository;

    /**
     * @var ScopePolicyManager
     */
    private $scopePolicyManager;

    public function __construct(ScopeRepository $scopeRepository, ScopePolicyManager $scopePolicyManager)
    {
        $this->scopeRepository = $scopeRepository;
        $this->scopePolicyManager = $scopePolicyManager;
    }

    public function check(AuthorizationRequest $authorization): void
    {
        $requestedScope = $this->getRequestedScope($authorization);
        $requestedScope = $this->scopePolicyManager->apply($requestedScope, $authorization->getClient());
        if ('' === $requestedScope) {
            return;
        }
        $scopes = explode(' ', $requestedScope);

        $availableScopes = $this->scopeRepository->all();
        if (0 !== \count(array_diff($scopes, $availableScopes))) {
            throw new \InvalidArgumentException(sprintf('An unsupported scope was requested. Available scopes are %s.', implode(', ', $availableScopes)));
        }
        $authorization->getMetadata()->set('scope', implode(' ', $scopes));
        $authorization->setResponseParameter('scope', implode(' ', $scopes)); //TODO: should be done after consent depending on approved scope
    }

    private function getRequestedScope(AuthorizationRequest $authorization): string
    {
        if ($authorization->hasQueryParam('scope')) {
            $requestedScope = $authorization->getQueryParam('scope');
            if (1 !== preg_match('/^[\x20\x23-\x5B\x5D-\x7E]+$/', $requestedScope)) {
                throw new \InvalidArgumentException('Invalid characters found in the "scope" parameter.');
            }
        } else {
            $requestedScope = '';
        }

        return $requestedScope;
    }
}
