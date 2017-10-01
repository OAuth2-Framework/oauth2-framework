<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Server\Model\Client\Rule;

use Assert\Assertion;
use Jose\Component\Encryption\JWEBuilder;
use Jose\Component\Signature\JWSBuilder;
use OAuth2Framework\Component\Server\Model\DataBag\DataBag;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;

final class UserinfoEndpointAlgorithmsRule implements RuleInterface
{
    /**
     * @var JWSBuilder|null
     */
    private $jwsBuilder;

    /**
     * @var JWEBuilder|null
     */
    private $jweBuilder;

    /**
     * UserinfoEndpointAlgorithmsRule constructor.
     *
     * @param JWSBuilder|null $jwsBuilder
     * @param JWEBuilder|null $jweBuilder
     */
    public function __construct(?JWSBuilder $jwsBuilder, ?JWEBuilder $jweBuilder)
    {
        $this->jwsBuilder = $jwsBuilder;
        $this->jweBuilder = $jweBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(DataBag $commandParameters, DataBag $validatedParameters, ? UserAccountId $userAccountId, callable $next): DataBag
    {
        if ($commandParameters->has('userinfo_signed_response_alg') && null !== $this->jwsBuilder) {
            Assertion::string($commandParameters->get('userinfo_signed_response_alg'), 'Invalid parameter \'userinfo_signed_response_alg\'. The value must be a string.');
            Assertion::inArray($commandParameters->get('userinfo_signed_response_alg'), $this->jwsBuilder->getSignatureAlgorithmManager()->list(), sprintf('The ID Token signature response algorithm \'%s\' is not supported. Please choose one of the following algorithm: %s', $commandParameters->get('userinfo_signed_response_alg'), implode(', ', $this->jwsBuilder->getSignatureAlgorithmManager()->list())));
            $validatedParameters = $validatedParameters->with('userinfo_signed_response_alg', $commandParameters->get('userinfo_signed_response_alg'));
        }

        if ($commandParameters->has('userinfo_encrypted_response_alg') && $commandParameters->has('userinfo_encrypted_response_enc') && null !== $this->jweBuilder) {
            Assertion::string($commandParameters->get('userinfo_encrypted_response_alg'), 'Invalid parameter \'userinfo_encrypted_response_alg\'. The value must be a string.');
            Assertion::string($commandParameters->get('userinfo_encrypted_response_enc'), 'Invalid parameter \'userinfo_encrypted_response_enc\'. The value must be a string.');
            Assertion::inArray($commandParameters->get('userinfo_encrypted_response_alg'), $this->jweBuilder->getKeyEncryptionAlgorithmManager()->list(), sprintf('The ID Token key encryption algorithm \'%s\' is not supported. Please choose one of the following algorithm: %s', $commandParameters->get('userinfo_encrypted_response_alg'), implode(', ', $this->jweBuilder->getKeyEncryptionAlgorithmManager()->list())));
            Assertion::inArray($commandParameters->get('userinfo_encrypted_response_enc'), $this->jweBuilder->getContentEncryptionAlgorithmManager()->list(), sprintf('The ID Token content encryption algorithm \'%s\' is not supported. Please choose one of the following algorithm: %s', $commandParameters->get('userinfo_encrypted_response_enc'), implode(', ', $this->jweBuilder->getContentEncryptionAlgorithmManager()->list())));
            $validatedParameters = $validatedParameters->with('userinfo_encrypted_response_alg', $commandParameters->get('userinfo_encrypted_response_alg'));
            $validatedParameters = $validatedParameters->with('userinfo_encrypted_response_enc', $commandParameters->get('userinfo_encrypted_response_enc'));
        }

        return $next($commandParameters, $validatedParameters, $userAccountId);
    }
}
