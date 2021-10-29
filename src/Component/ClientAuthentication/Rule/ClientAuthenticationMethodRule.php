<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\ClientAuthentication\Rule;

use InvalidArgumentException;
use function is_string;
use OAuth2Framework\Component\ClientAuthentication\AuthenticationMethodManager;
use OAuth2Framework\Component\ClientRule\Rule;
use OAuth2Framework\Component\ClientRule\RuleHandler;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;

final class ClientAuthenticationMethodRule implements Rule
{
    public function __construct(
        private AuthenticationMethodManager $clientAuthenticationMethodManager
    ) {
    }

    public function handle(
        ClientId $clientId,
        DataBag $commandParameters,
        DataBag $validatedParameters,
        RuleHandler $next
    ): DataBag {
        if (! $commandParameters->has('token_endpoint_auth_method')) {
            $commandParameters->set('token_endpoint_auth_method', 'client_secret_basic');
        }

        if (! is_string($commandParameters->get('token_endpoint_auth_method'))) {
            throw new InvalidArgumentException('The parameter "token_endpoint_auth_method" must be a string.');
        }
        if (! $this->clientAuthenticationMethodManager->has($commandParameters->get('token_endpoint_auth_method'))) {
            throw new InvalidArgumentException(sprintf(
                'The token endpoint authentication method "%s" is not supported. Please use one of the following values: %s',
                $commandParameters->get('token_endpoint_auth_method'),
                implode(', ', $this->clientAuthenticationMethodManager->list())
            ));
        }

        $clientAuthenticationMethod = $this->clientAuthenticationMethodManager->get(
            $commandParameters->get('token_endpoint_auth_method')
        );
        $validatedParameters = $next->handle($clientId, $commandParameters, $validatedParameters);
        $validatedParameters = $clientAuthenticationMethod->checkClientConfiguration(
            $commandParameters,
            $validatedParameters
        );
        $validatedParameters->set('token_endpoint_auth_method', $commandParameters->get('token_endpoint_auth_method'));

        return $validatedParameters;
    }
}
