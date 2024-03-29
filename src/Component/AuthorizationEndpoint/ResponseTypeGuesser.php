<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationEndpoint;

use Assert\Assertion;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseType;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseTypeManager;

class ResponseTypeGuesser
{
    public function __construct(
        private ResponseTypeManager $responseTypeManager
    ) {
    }

    public function get(AuthorizationRequest $authorization): ResponseType
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

        return $this->responseTypeManager->get($responseTypeName);
    }
}
