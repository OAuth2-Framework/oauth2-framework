<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Scope\Rule;

use InvalidArgumentException;
use function is_string;
use OAuth2Framework\Component\ClientRule\Rule;
use OAuth2Framework\Component\ClientRule\RuleHandler;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;

final class ScopePolicyDefaultRule implements Rule
{
    public function handle(
        ClientId $clientId,
        DataBag $commandParameters,
        DataBag $validatedParameters,
        RuleHandler $next
    ): DataBag {
        if ($commandParameters->has('default_scope')) {
            $defaultScope = $commandParameters->get('default_scope');
            if (! is_string($defaultScope)) {
                throw new InvalidArgumentException('The "default_scope" parameter must be a string.');
            }
            if (preg_match('/^[\x20\x23-\x5B\x5D-\x7E]+$/', $defaultScope) !== 1) {
                throw new InvalidArgumentException('Invalid characters found in the "default_scope" parameter.');
            }
            $validatedParameters->set('default_scope', $commandParameters->get('default_scope'));
        }

        return $next->handle($clientId, $commandParameters, $validatedParameters);
    }
}
