<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker;

use Assert\Assertion;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseTypeManager;

final class ResponseTypeParameterChecker implements ParameterChecker
{
    public function __construct(
        private readonly ResponseTypeManager $responseTypeManager
    ) {
    }

    public static function create(ResponseTypeManager $responseTypeManager): static
    {
        return new self($responseTypeManager);
    }

    public function check(AuthorizationRequest $authorization): void
    {
        // @see http://tools.ietf.org/html/rfc6749#section-3.1.1
        Assertion::true($authorization->hasQueryParam('response_type'), 'The parameter "response_type" is mandatory.');
        $responseTypeName = $authorization->getQueryParam('response_type');
        Assertion::true(
            $this->responseTypeManager->has($responseTypeName),
            sprintf('The response type "%s" is not supported by this server', $responseTypeName)
        );
        Assertion::true(
            $authorization->getClient()
                ->isResponseTypeAllowed($responseTypeName),
            sprintf('The response type "%s" is not allowed for this client.', $responseTypeName)
        ); // Should try to find the response mode before exception
    }
}
