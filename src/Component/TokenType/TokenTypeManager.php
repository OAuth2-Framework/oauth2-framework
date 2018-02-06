<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\TokenType;

use Psr\Http\Message\ServerRequestInterface;

class TokenTypeManager
{
    /**
     * @var TokenType[]
     */
    private $tokenTypes = [];

    /**
     * @var null|string
     */
    private $defaultTokenType = null;

    /**
     * @param TokenType $tokenType
     * @param bool      $default
     *
     * @return TokenTypeManager
     */
    public function add(TokenType $tokenType, bool $default = false): self
    {
        $this->tokenTypes[$tokenType->name()] = $tokenType;
        if (null === $this->defaultTokenType || true === $default) {
            $this->defaultTokenType = $tokenType->name();
        }

        return $this;
    }

    /**
     * @param string $tokenTypeName
     *
     * @return bool
     */
    public function has(string $tokenTypeName): bool
    {
        return array_key_exists($tokenTypeName, $this->tokenTypes);
    }

    /**
     * @param string $tokenTypeName
     *
     * @return TokenType
     */
    public function get(string $tokenTypeName): TokenType
    {
        if (!$this->has($tokenTypeName)) {
            throw new \InvalidArgumentException(sprintf('Unsupported token type "%s".', $tokenTypeName));
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

    /**
     * @return TokenType
     */
    public function getDefault(): TokenType
    {
        if (null === $this->defaultTokenType) {
            throw new \LogicException('No default token type set.');
        }

        return $this->get($this->defaultTokenType);
    }

    /**
     * @param ServerRequestInterface $request
     * @param array                  $additionalCredentialValues
     * @param TokenType|null         $type
     *
     * @return string|null
     */
    public function findToken(ServerRequestInterface $request, array &$additionalCredentialValues, ?TokenType &$type = null): ?string
    {
        foreach ($this->all() as $tmp_type) {
            $tmpAdditionalCredentialValues = [];
            $token = $tmp_type->find($request, $tmpAdditionalCredentialValues);

            if (null !== $token) {
                $additionalCredentialValues = $tmpAdditionalCredentialValues;
                $type = $tmp_type;

                return $token;
            }
        }

        return null;
    }

    /**
     * @param array $additionalAuthenticationParameters
     *
     * @return array
     */
    public function getSchemes(array $additionalAuthenticationParameters = []): array
    {
        $schemes = [];
        foreach ($this->all() as $type) {
            $schemes[] = $this->computeScheme($type, $additionalAuthenticationParameters);
        }

        return $schemes;
    }

    /**
     * @param TokenType $type
     * @param array     $additionalAuthenticationParameters
     *
     * @return string
     */
    private function computeScheme(TokenType $type, array $additionalAuthenticationParameters): string
    {
        $scheme = trim($type->getScheme());
        if (0 === count($additionalAuthenticationParameters)) {
            return $scheme;
        }

        foreach (['all', $type->name()] as $key) {
            if (array_key_exists($key, $additionalAuthenticationParameters)) {
                $scheme = $this->appendParameters($scheme, $additionalAuthenticationParameters[$key]);
            }
        }

        return $scheme;
    }

    /**
     * @param string $scheme
     * @param array  $parameters
     *
     * @return string
     */
    private function appendParameters(string $scheme, array $parameters): string
    {
        $position = mb_strpos($scheme, ' ', 0, 'utf-8');
        $add_comma = false === $position ? false : true;

        foreach ($parameters as $key => $value) {
            $value = is_string($value) ? sprintf('"%s"', $value) : $value;
            if (false === $add_comma) {
                $add_comma = true;
                $scheme = sprintf('%s %s=%s', $scheme, $key, $value);
            } else {
                $scheme = sprintf('%s,%s=%s', $scheme, $key, $value);
            }
        }

        return $scheme;
    }
}
