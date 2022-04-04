<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Scope;

use function count;
use InvalidArgumentException;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\ParameterChecker;
use OAuth2Framework\Component\Scope\Policy\ScopePolicyManager;

class ScopeParameterChecker implements ParameterChecker
{
    public function __construct(
        private readonly ScopeRepository $scopeRepository,
        private readonly ScopePolicyManager $scopePolicyManager
    ) {
    }

    public static function create(ScopeRepository $scopeRepository, ScopePolicyManager $scopePolicyManager): static
    {
        return new self($scopeRepository, $scopePolicyManager);
    }

    public function check(AuthorizationRequest $authorization): void
    {
        $requestedScope = $this->getRequestedScope($authorization);
        $requestedScope = $this->scopePolicyManager->apply($requestedScope, $authorization->getClient());
        if ($requestedScope === '') {
            return;
        }
        $scopes = explode(' ', $requestedScope);

        $availableScopes = $this->scopeRepository->all();
        if (count(array_diff($scopes, $availableScopes)) !== 0) {
            throw new InvalidArgumentException(sprintf(
                'An unsupported scope was requested. Available scopes are %s.',
                implode(', ', $availableScopes)
            ));
        }
        $authorization->getMetadata()
            ->set('scope', implode(' ', $scopes))
        ;
        $authorization->setResponseParameter(
            'scope',
            implode(' ', $scopes)
        ); //TODO: should be done after consent depending on approved scope
    }

    private function getRequestedScope(AuthorizationRequest $authorization): string
    {
        if ($authorization->hasQueryParam('scope')) {
            $requestedScope = $authorization->getQueryParam('scope');
            if (preg_match('/^[\x20\x23-\x5B\x5D-\x7E]+$/', (string) $requestedScope) !== 1) {
                throw new InvalidArgumentException('Invalid characters found in the "scope" parameter.');
            }
        } else {
            $requestedScope = '';
        }

        return $requestedScope;
    }
}
