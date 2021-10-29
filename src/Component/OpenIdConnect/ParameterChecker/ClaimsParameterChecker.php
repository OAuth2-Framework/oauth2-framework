<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\OpenIdConnect\ParameterChecker;

use InvalidArgumentException;
use function is_array;
use const JSON_THROW_ON_ERROR;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\ParameterChecker;

final class ClaimsParameterChecker implements ParameterChecker
{
    public function check(AuthorizationRequest $authorization): void
    {
        if (! $authorization->hasQueryParam('claims')) {
            return;
        }
        $decoded = json_decode($authorization->getQueryParam('claims'), true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($decoded)) {
            throw new InvalidArgumentException('Invalid "claims" parameter.');
        }

        $authorization->getMetadata()
            ->set('claims', $authorization->getQueryParam('claims'))
        ;
    }
}
