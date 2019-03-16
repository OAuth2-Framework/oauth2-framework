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

namespace OAuth2Framework\Component\MacTokenType;

use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Core\TokenType\TokenType;
use Psr\Http\Message\ServerRequestInterface;

abstract class MacToken implements TokenType
{
    /**
     * @var int
     */
    private $timestampLifetime;

    /**
     * @var string
     */
    private $macAlgorithm;

    /**
     * MacToken constructor.
     */
    public function __construct(string $macAlgorithm, int $timestampLifetime)
    {
        if (!\in_array($macAlgorithm, \array_keys($this->getAlgorithmMap()), true)) {
            throw new \InvalidArgumentException('Unsupported ma algorithm.');
        }
        if ($timestampLifetime <= 0) {
            throw new \InvalidArgumentException(('Invalid timestamp lifetime.'));
        }
        $this->macAlgorithm = $macAlgorithm;
        $this->timestampLifetime = $timestampLifetime;
    }

    public function name(): string
    {
        return 'MAC';
    }

    public function getScheme(): string
    {
        return $this->name();
    }

    public function getAdditionalInformation(): array
    {
        return [
            'mac_key' => $this->generateMacKey(),
            'mac_algorithm' => $this->getMacAlgorithm(),
        ];
    }

    public function getTimestampLifetime(): int
    {
        return $this->timestampLifetime;
    }

    /**
     * @return string
     */
    public function getMacAlgorithm()
    {
        return $this->macAlgorithm;
    }

    public function find(ServerRequestInterface $request, array &$additionalCredentialValues): ?string
    {
        $authorization_headers = $request->getHeader('AUTHORIZATION');

        foreach ($authorization_headers as $authorization_header) {
            if ('MAC ' === \mb_substr($authorization_header, 0, 4, '8bit')) {
                $header = \trim(\mb_substr($authorization_header, 4, null, '8bit'));
                if (true === $this->isHeaderValid($header, $additionalCredentialValues, $token)) {
                    return $token;
                }
            }
        }

        return null;
    }

    public function isRequestValid(AccessToken $token, ServerRequestInterface $request, array $additionalCredentialValues): bool
    {
        if ($token->getParameter()->get('token_type') !== $this->name()) {
            return false;
        }

        foreach ($this->getParametersToCheck() as $key => $closure) {
            if (!\array_key_exists($key, $additionalCredentialValues) || false === $closure($additionalCredentialValues[$key], $token)) {
                return false;
            }
        }

        $mac = $this->generateMac($request, $token, $additionalCredentialValues);

        return \hash_equals($mac, $additionalCredentialValues['mac']);
    }

    private function getParametersToCheck(): array
    {
        return [
            'id' => function ($value, AccessToken $token) {
                return \hash_equals($token->getId()->getValue(), $value);
            },
            'ts' => function ($value) {
                return \time() < $this->getTimestampLifetime() + (int) $value;
            },
            'nonce' => function () {
                return true;
            },
        ];
    }

    private function generateMac(ServerRequestInterface $request, AccessToken $token, array $values): string
    {
        $timestamp = $values['ts'];
        $nonce = $values['nonce'];
        $method = $request->getMethod();
        $request_uri = $request->getRequestTarget();
        $host = $request->getUri()->getHost();
        $port = $request->getUri()->getPort();
        $ext = \array_key_exists('ext', $values) ? $values['ext'] : null;

        $basestr =
            $timestamp."\n".
            $nonce."\n".
            $method."\n".
            $request_uri."\n".
            $host."\n".
            $port."\n".
            $ext."\n";

        $algorithms = $this->getAlgorithmMap();
        if (!\array_key_exists($token->getParameter()->get('mac_algorithm'), $algorithms)) {
            throw new \RuntimeException(\Safe\sprintf('The MAC algorithm "%s" is not supported.', $token->getParameter()->get('mac_algorithm')));
        }

        return \base64_encode(\hash_hmac(
            $algorithms[$token->getParameter()->get('mac_algorithm')],
            $basestr,
            $token->getParameter()->get('mac_key'),
            true
        ));
    }

    protected function getAlgorithmMap(): array
    {
        return [
            'hmac-sha-1' => 'sha1',
            'hmac-sha-256' => 'sha256',
        ];
    }

    private function isHeaderValid(string $header, array &$additionalCredentialValues, string &$token = null): bool
    {
        if (1 === \Safe\preg_match('/(\w+)=("((?:[^"\\\\]|\\\\.)+)"|([^\s,$]+))/', $header, $matches)) {
            \Safe\preg_match_all('/(\w+)=("((?:[^"\\\\]|\\\\.)+)"|([^\s,$]+))/', $header, $matches, PREG_SET_ORDER);

            if (!\is_array($matches)) {
                return false;
            }

            $values = [];
            foreach ($matches as $match) {
                $values[$match[1]] = $match[4] ?? $match[3];
            }

            if (\array_key_exists('id', $values)) {
                $additionalCredentialValues = $values;

                $token = $values['id'];

                return true;
            }
        }

        return false;
    }

    abstract protected function generateMacKey(): string;
}
