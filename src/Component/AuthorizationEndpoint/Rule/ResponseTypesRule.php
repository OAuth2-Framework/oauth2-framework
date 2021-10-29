<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationEndpoint\Rule;

use Assert\Assertion;
use OAuth2Framework\Component\ClientRule\Rule;
use OAuth2Framework\Component\ClientRule\RuleHandler;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;

final class ResponseTypesRule implements Rule
{
    public function handle(
        ClientId $clientId,
        DataBag $commandParameters,
        DataBag $validatedParameters,
        RuleHandler $next
    ): DataBag {
        if (! $commandParameters->has('response_types')) {
            $commandParameters->set('response_types', []);
        }
        $this->checkResponseTypes($commandParameters);

        $validatedParameters->set('response_types', $commandParameters->get('response_types'));

        return $next->handle($clientId, $commandParameters, $validatedParameters);
    }

    private function checkResponseTypes(DataBag $parameters): void
    {
        $responseTypes = $parameters->get('response_types');
        Assertion::isArray($responseTypes, 'The parameter "response_types" must be an array of strings.');
        Assertion::allString($responseTypes, 'The parameter "response_types" must be an array of strings.');
    }
}
