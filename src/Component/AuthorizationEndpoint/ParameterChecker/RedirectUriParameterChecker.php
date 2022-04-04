<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker;

use Assert\Assertion;
use function count;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;

final class RedirectUriParameterChecker implements ParameterChecker
{
    public static function create(): static
    {
        return new self();
    }

    public function check(AuthorizationRequest $authorization): void
    {
        $redirectUri = $authorization->getRedirectUri();
        $availableRedirectUris = $this->getRedirectUris($authorization);
        if (count($availableRedirectUris) > 0) {
            Assertion::inArray(
                $redirectUri,
                $availableRedirectUris,
                sprintf('The redirect URI "%s" is not registered.', $redirectUri)
            );
        }
    }

    /**
     * @return string[]
     */
    private function getRedirectUris(AuthorizationRequest $authorization): array
    {
        return $authorization->getClient()
            ->has('redirect_uris') ? $authorization->getClient()
            ->get('redirect_uris') : [];
    }
}
