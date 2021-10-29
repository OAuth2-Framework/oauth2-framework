<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\ClientRegistrationEndpoint\Rule;

use Assert\Assertion;
use function in_array;
use InvalidArgumentException;
use function is_array;
use function is_string;
use Jose\Component\Core\JWKSet;
use Jose\Component\Signature\JWSLoader;
use const JSON_THROW_ON_ERROR;
use OAuth2Framework\Component\ClientRule\Rule;
use OAuth2Framework\Component\ClientRule\RuleHandler;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use Throwable;

final class SoftwareRule implements Rule
{
    public function __construct(
        private JWSLoader $jwsLoader,
        private JWKSet $softwareStatementSignatureKeySet,
        private bool $isSoftwareStatementRequired,
        private array $allowedSignatureAlgorithms
    ) {
    }

    public function isSoftwareStatementRequired(): bool
    {
        return $this->isSoftwareStatementRequired;
    }

    public function handle(
        ClientId $clientId,
        DataBag $commandParameters,
        DataBag $validatedParameters,
        RuleHandler $next
    ): DataBag {
        if ($this->isSoftwareStatementRequired() && ! $commandParameters->has('software_statement')) {
            throw new InvalidArgumentException('The parameter "software_statement" is mandatory.');
        }
        if ($commandParameters->has('software_statement')) {
            $statement = $commandParameters->get('software_statement');
            if (! is_string($statement)) {
                throw new InvalidArgumentException('The software statement must be a string.');
            }
            $software_statement = $this->loadSoftwareStatement($statement);
            $validatedParameters->set('software_statement', $commandParameters->get('software_statement'));
        } else {
            $software_statement = [];
        }

        foreach (['software_id', 'software_version'] as $key) {
            if ($commandParameters->has($key)) {
                $validatedParameters->set($key, $commandParameters->get($key));
            }
        }

        $validatedParameters = $next->handle($clientId, $commandParameters, $validatedParameters);
        foreach ($software_statement as $k => $v) {
            $validatedParameters->set($k, $v);
        }

        return $validatedParameters;
    }

    private function loadSoftwareStatement(string $software_statement): array
    {
        try {
            $signatureVerified = null;
            $jws = $this->jwsLoader->loadAndVerifyWithKeySet(
                $software_statement,
                $this->softwareStatementSignatureKeySet,
                $signatureVerified
            );
            if (! in_array(
                $jws->getSignature($signatureVerified)
                    ->getProtectedHeaderParameter('alg'),
                $this->allowedSignatureAlgorithms,
                true
            )) {
                throw new InvalidArgumentException('Invalid Software Statement.');
            }
            $payload = $jws->getPayload();
            Assertion::string($payload, 'The JWS payload is not available');
            $claims = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($claims)) {
                throw new InvalidArgumentException('Invalid Software Statement.');
            }

            return $claims;
        } catch (Throwable $e) {
            throw new InvalidArgumentException('Invalid Software Statement.', $e->getCode(), $e);
        }
    }
}
