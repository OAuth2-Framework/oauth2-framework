<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\OpenIdConnect\Rule;

use function in_array;
use InvalidArgumentException;
use function is_string;
use Jose\Component\Encryption\JWEBuilder;
use Jose\Component\Signature\JWSBuilder;
use OAuth2Framework\Component\ClientRule\Rule;
use OAuth2Framework\Component\ClientRule\RuleHandler;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;

final class UserinfoEndpointAlgorithmsRule implements Rule
{
    public function __construct(
        private ?JWSBuilder $jwsBuilder,
        private ?JWEBuilder $jweBuilder
    ) {
    }

    public function handle(
        ClientId $clientId,
        DataBag $commandParameters,
        DataBag $validatedParameters,
        RuleHandler $next
    ): DataBag {
        if ($commandParameters->has('userinfo_signed_response_alg') && $this->jwsBuilder !== null) {
            $this->checkAlgorithms(
                'userinfo_signed_response_alg',
                $commandParameters,
                $this->jwsBuilder->getSignatureAlgorithmManager()
                    ->list()
            );
            $validatedParameters->set(
                'userinfo_signed_response_alg',
                $commandParameters->get('userinfo_signed_response_alg')
            );
        }

        if ($commandParameters->has('userinfo_encrypted_response_alg') && $commandParameters->has(
            'userinfo_encrypted_response_enc'
        ) && $this->jweBuilder !== null) {
            $this->checkAlgorithms(
                'userinfo_encrypted_response_alg',
                $commandParameters,
                $this->jweBuilder->getKeyEncryptionAlgorithmManager()
                    ->list()
            );
            $validatedParameters->set(
                'userinfo_encrypted_response_alg',
                $commandParameters->get('userinfo_encrypted_response_alg')
            );
            $this->checkAlgorithms(
                'userinfo_encrypted_response_enc',
                $commandParameters,
                $this->jweBuilder->getContentEncryptionAlgorithmManager()
                    ->list()
            );
            $validatedParameters->set(
                'userinfo_encrypted_response_enc',
                $commandParameters->get('userinfo_encrypted_response_enc')
            );
        }

        return $next->handle($clientId, $commandParameters, $validatedParameters);
    }

    private function checkAlgorithms(string $parameter, DataBag $commandParameters, array $allowedAlgorithms): void
    {
        $algorithm = $commandParameters->get($parameter);
        if (! is_string($algorithm) || ! in_array($algorithm, $allowedAlgorithms, true)) {
            throw new InvalidArgumentException(sprintf(
                'The parameter "%s" must be an algorithm supported by this server. Please choose one of the following value(s): %s',
                $parameter,
                implode(', ', $allowedAlgorithms)
            ));
        }
    }
}
