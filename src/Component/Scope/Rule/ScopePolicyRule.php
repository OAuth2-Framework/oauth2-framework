<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Scope\Rule;

use InvalidArgumentException;
use function is_string;
use OAuth2Framework\Component\ClientRule\Rule;
use OAuth2Framework\Component\ClientRule\RuleHandler;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Scope\Policy\ScopePolicyManager;

final class ScopePolicyRule implements Rule
{
    public function __construct(
        private ScopePolicyManager $scopePolicyManager
    ) {
    }

    public function handle(
        ClientId $clientId,
        DataBag $commandParameters,
        DataBag $validatedParameters,
        RuleHandler $next
    ): DataBag {
        if ($commandParameters->has('scope_policy')) {
            $policy = $commandParameters->get('scope_policy');
            if (! is_string($policy)) {
                throw new InvalidArgumentException('The parameter "scope_policy" must be a string.');
            }
            if (! $this->scopePolicyManager->has($policy)) {
                throw new InvalidArgumentException(sprintf('The scope policy "%s" is not supported.', $policy));
            }
            $validatedParameters->set('scope_policy', $commandParameters->get('scope_policy'));
        }

        return $next->handle($clientId, $commandParameters, $validatedParameters);
    }
}
