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
use Jose\EncrypterInterface;
use Jose\SignerInterface;
use OAuth2Framework\Component\Server\Model\DataBag\DataBag;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;

final class IdTokenAlgorithmsRule implements RuleInterface
{
    /**
     * @var SignerInterface
     */
    private $signer;

    /**
     * @var EncrypterInterface|null
     */
    private $encrypter;

    /**
     * IdTokenAlgorithmsRule constructor.
     *
     * @param SignerInterface         $signer
     * @param EncrypterInterface|null $encrypter
     */
    public function __construct(SignerInterface $signer, ?EncrypterInterface $encrypter)
    {
        $this->signer = $signer;
        $this->encrypter = $encrypter;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(DataBag $commandParameters, DataBag $validatedParameters, ? UserAccountId $userAccountId, callable $next): DataBag
    {
        if ($commandParameters->has('id_token_signed_response_alg')) {
            Assertion::string($commandParameters['id_token_signed_response_alg'], 'Invalid parameter \'id_token_signed_response_alg\'. The value must be a string.');
            Assertion::inArray($commandParameters['id_token_signed_response_alg'], $this->signer->getSupportedSignatureAlgorithms(), sprintf('The ID Token signature response algorithm \'%s\' is not supported. Please choose one of the following algorithm: %s', $commandParameters->get('id_token_signed_response_alg'), implode(', ', $this->signer->getSupportedSignatureAlgorithms())));
            $validatedParameters = $validatedParameters->with('id_token_signed_response_alg', $commandParameters['id_token_signed_response_alg']);
        }

        if ($commandParameters->has('id_token_encrypted_response_alg') && $commandParameters->has('id_token_encrypted_response_enc') && null !== $this->encrypter) {
            Assertion::string($commandParameters['id_token_encrypted_response_alg'], 'Invalid parameter \'id_token_encrypted_response_alg\'. The value must be a string.');
            Assertion::string($commandParameters['id_token_encrypted_response_enc'], 'Invalid parameter \'id_token_encrypted_response_enc\'. The value must be a string.');
            Assertion::inArray($commandParameters['id_token_encrypted_response_alg'], $this->encrypter->getSupportedKeyEncryptionAlgorithms(), sprintf('The ID Token content encryption algorithm \'%s\' is not supported. Please choose one of the following algorithm: %s', $commandParameters->get('id_token_encrypted_response_alg'), implode(', ', $this->encrypter->getSupportedContentEncryptionAlgorithms())));
            Assertion::inArray($commandParameters['id_token_encrypted_response_enc'], $this->encrypter->getSupportedContentEncryptionAlgorithms(), sprintf('The ID Token key encryption algorithm \'%s\' is not supported. Please choose one of the following algorithm: %s', $commandParameters->get('id_token_encrypted_response_enc'), implode(', ', $this->encrypter->getSupportedKeyEncryptionAlgorithms())));
            $validatedParameters = $validatedParameters->with('id_token_encrypted_response_alg', $commandParameters['id_token_encrypted_response_alg']);
            $validatedParameters = $validatedParameters->with('id_token_encrypted_response_enc', $commandParameters['id_token_encrypted_response_enc']);
        }

        return $next($commandParameters, $validatedParameters, $userAccountId);
    }
}
