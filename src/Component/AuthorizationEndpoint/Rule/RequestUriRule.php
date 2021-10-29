<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationEndpoint\Rule;

use Assert\Assertion;
use function count;
use const FILTER_VALIDATE_URL;
use OAuth2Framework\Component\ClientRule\Rule;
use OAuth2Framework\Component\ClientRule\RuleHandler;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;

final class RequestUriRule implements Rule
{
    public function handle(
        ClientId $clientId,
        DataBag $commandParameters,
        DataBag $validatedParameters,
        RuleHandler $next
    ): DataBag {
        $validatedParameters = $next->handle($clientId, $commandParameters, $validatedParameters);
        if (! $validatedParameters->has('response_types') || count($validatedParameters->get('response_types')) === 0) {
            return $validatedParameters;
        }
        if ($commandParameters->has('request_uris')) {
            $this->checkAllUris($commandParameters->get('request_uris'));
            $validatedParameters->set('request_uris', $commandParameters->get('request_uris'));
        }

        return $validatedParameters;
    }

    private function checkAllUris(mixed $value): void
    {
        Assertion::isArray($value, 'The parameter "request_uris" must be a list of URI.');
        foreach ($value as $redirectUri) {
            Assertion::string($redirectUri, 'The parameter "request_uris" must be a list of URI.');
            Assertion::true(
                filter_var($redirectUri, FILTER_VALIDATE_URL) !== false,
                'The parameter "request_uris" must be a list of URI.'
            ); //TODO: URN should be allowed
        }
    }
}
