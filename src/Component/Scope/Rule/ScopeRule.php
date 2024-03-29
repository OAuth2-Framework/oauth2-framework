<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Scope\Rule;

use InvalidArgumentException;
use function is_string;
use OAuth2Framework\Component\ClientRule\Rule;
use OAuth2Framework\Component\ClientRule\RuleHandler;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;

final class ScopeRule implements Rule
{
    public function handle(
        ClientId $clientId,
        DataBag $commandParameters,
        DataBag $validatedParameters,
        RuleHandler $next
    ): DataBag {
        if ($commandParameters->has('scope')) {
            $scope = $commandParameters->get('scope');
            if (! is_string($scope)) {
                throw new InvalidArgumentException('The "scope" parameter must be a string.');
            }
            if (preg_match('/^[\x20\x23-\x5B\x5D-\x7E]+$/', $scope) !== 1) {
                throw new InvalidArgumentException('Invalid characters found in the "scope" parameter.');
            }
            $validatedParameters->set('scope', $scope);
        }

        return $next->handle($clientId, $commandParameters, $validatedParameters);
    }
}
