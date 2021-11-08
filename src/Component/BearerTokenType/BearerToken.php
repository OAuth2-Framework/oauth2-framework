<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\BearerTokenType;

use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Core\TokenType\TokenType;
use Psr\Http\Message\ServerRequestInterface;

final class BearerToken implements TokenType
{
    /**
     * @var TokenFinder[]
     */
    private array $tokenFinders = [];

    public function __construct(
        private string $realm
    ) {
    }

    public static function create(string $realm): self
    {
        return new self($realm);
    }

    public function addTokenFinder(TokenFinder $tokenFinder): self
    {
        $this->tokenFinders[] = $tokenFinder;

        return $this;
    }

    public function name(): string
    {
        return 'Bearer';
    }

    public function getScheme(): string
    {
        return sprintf('%s realm="%s"', $this->name(), $this->realm);
    }

    public function getAdditionalInformation(): array
    {
        return [];
    }

    public function find(ServerRequestInterface $request, array &$additionalCredentialValues): ?string
    {
        foreach ($this->tokenFinders as $finder) {
            $token = $finder->find($request, $additionalCredentialValues);
            if ($token !== null) {
                return $token;
            }
        }

        return null;
    }

    public function isRequestValid(
        AccessToken $token,
        ServerRequestInterface $request,
        array $additionalCredentialValues
    ): bool {
        if (! $token->getParameter()->has('token_type')) {
            return false;
        }

        return $token->getParameter()
            ->get('token_type') === $this->name();
    }
}
