<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\MacTokenType;

use function array_key_exists;
use InvalidArgumentException;
use function is_array;
use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Core\TokenType\TokenType;
use const PREG_SET_ORDER;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

abstract class MacToken implements TokenType
{
    private int $timestampLifetime;

    private string $macAlgorithm;

    public function __construct(string $macAlgorithm, int $timestampLifetime)
    {
        if (! array_key_exists($macAlgorithm, $this->getAlgorithmMap())) {
            throw new InvalidArgumentException('Unsupported ma algorithm.');
        }
        if ($timestampLifetime <= 0) {
            throw new InvalidArgumentException(('Invalid timestamp lifetime.'));
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

    public function getMacAlgorithm(): string
    {
        return $this->macAlgorithm;
    }

    public function find(ServerRequestInterface $request, array &$additionalCredentialValues): ?string
    {
        $authorization_headers = $request->getHeader('AUTHORIZATION');

        foreach ($authorization_headers as $authorization_header) {
            if (mb_strpos($authorization_header, 'MAC ', 0, '8bit') === 0) {
                $header = trim(mb_substr($authorization_header, 4, null, '8bit'));
                if ($this->isHeaderValid($header, $additionalCredentialValues, $token) === true) {
                    return $token;
                }
            }
        }

        return null;
    }

    public function isRequestValid(
        AccessToken $token,
        ServerRequestInterface $request,
        array $additionalCredentialValues
    ): bool {
        if ($token->getParameter()->get('token_type') !== $this->name()) {
            return false;
        }

        foreach ($this->getParametersToCheck() as $key => $closure) {
            if (! array_key_exists(
                $key,
                $additionalCredentialValues
            ) || $closure($additionalCredentialValues[$key], $token) === false) {
                return false;
            }
        }

        $mac = $this->generateMac($request, $token, $additionalCredentialValues);

        return hash_equals($mac, $additionalCredentialValues['mac']);
    }

    protected function getAlgorithmMap(): array
    {
        return [
            'hmac-sha-1' => 'sha1',
            'hmac-sha-256' => 'sha256',
        ];
    }

    abstract protected function generateMacKey(): string;

    private function getParametersToCheck(): array
    {
        $timestampLifetime = $this->getTimestampLifetime();
        return [
            'id' => static function (string $value, AccessToken $token): bool {
                return hash_equals($token->getId()->getValue(), $value);
            },
            'ts' => static function (int $value) use ($timestampLifetime): bool {
                return time() < $timestampLifetime + $value;
            },
            'nonce' => static function (): bool {
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
        $host = $request->getUri()
            ->getHost()
        ;
        $port = $request->getUri()
            ->getPort()
        ;
        $ext = $values['ext'] ?? null;

        $basestr =
            $timestamp . "\n" .
            $nonce . "\n" .
            $method . "\n" .
            $request_uri . "\n" .
            $host . "\n" .
            $port . "\n" .
            $ext . "\n";

        $algorithms = $this->getAlgorithmMap();
        if (! array_key_exists($token->getParameter()->get('mac_algorithm'), $algorithms)) {
            throw new RuntimeException(sprintf(
                'The MAC algorithm "%s" is not supported.',
                $token->getParameter()->get('mac_algorithm')
            ));
        }

        return base64_encode(hash_hmac(
            $algorithms[$token->getParameter()->get('mac_algorithm')],
            $basestr,
            $token->getParameter()
                ->get('mac_key'),
            true
        ));
    }

    private function isHeaderValid(string $header, array &$additionalCredentialValues, string &$token = null): bool
    {
        if (preg_match('/(\w+)=("((?:[^"\\\\]|\\\\.)+)"|([^\s,$]+))/', $header, $matches) === 1) {
            preg_match_all('/(\w+)=("((?:[^"\\\\]|\\\\.)+)"|([^\s,$]+))/', $header, $matches, PREG_SET_ORDER);

            if (! is_array($matches)) {
                return false;
            }

            $values = [];
            foreach ($matches as $match) {
                $values[$match[1]] = $match[4] ?? $match[3];
            }

            if (array_key_exists('id', $values)) {
                $additionalCredentialValues = $values;

                $token = $values['id'];

                return true;
            }
        }

        return false;
    }
}
