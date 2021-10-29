<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Core\TokenType;

use function array_key_exists;
use InvalidArgumentException;
use function is_string;
use LogicException;
use Psr\Http\Message\ServerRequestInterface;

class TokenTypeManager
{
    /**
     * @var TokenType[]
     */
    private array $tokenTypes = [];

    private ?string $defaultTokenType = null;

    public function add(TokenType $tokenType, bool $default = false): void
    {
        $this->tokenTypes[$tokenType->name()] = $tokenType;
        if ($this->defaultTokenType === null || $default === true) {
            $this->defaultTokenType = $tokenType->name();
        }
    }

    public function has(string $tokenTypeName): bool
    {
        return array_key_exists($tokenTypeName, $this->tokenTypes);
    }

    public function get(string $tokenTypeName): TokenType
    {
        if (! $this->has($tokenTypeName)) {
            throw new InvalidArgumentException(sprintf('Unsupported token type "%s".', $tokenTypeName));
        }

        return $this->tokenTypes[$tokenTypeName];
    }

    /**
     * @return TokenType[]
     */
    public function all(): array
    {
        return $this->tokenTypes;
    }

    public function getDefault(): TokenType
    {
        if ($this->defaultTokenType === null) {
            throw new LogicException('No default token type set.');
        }

        return $this->get($this->defaultTokenType);
    }

    public function findToken(
        ServerRequestInterface $request,
        array &$additionalCredentialValues,
        ?TokenType &$type = null
    ): ?string {
        foreach ($this->all() as $tmp_type) {
            $tmpAdditionalCredentialValues = [];
            $token = $tmp_type->find($request, $tmpAdditionalCredentialValues);

            if ($token !== null) {
                $additionalCredentialValues = $tmpAdditionalCredentialValues;
                $type = $tmp_type;

                return $token;
            }
        }

        return null;
    }

    public function getSchemes(array $additionalAuthenticationParameters = []): array
    {
        $schemes = [];
        foreach ($this->all() as $type) {
            $schemes[] = $this->appendParameters($type->getScheme(), $additionalAuthenticationParameters);
        }

        return $schemes;
    }

    private function appendParameters(string $scheme, array $parameters): string
    {
        $position = mb_strpos($scheme, ' ', 0, 'utf-8');
        $add_comma = $position === false ? false : true;

        foreach ($parameters as $key => $value) {
            $value = is_string($value) ? sprintf('"%s"', $value) : $value;
            if ($add_comma === false) {
                $add_comma = true;
                $scheme = sprintf('%s %s=%s', $scheme, $key, $value);
            } else {
                $scheme = sprintf('%s,%s=%s', $scheme, $key, $value);
            }
        }

        return $scheme;
    }
}
