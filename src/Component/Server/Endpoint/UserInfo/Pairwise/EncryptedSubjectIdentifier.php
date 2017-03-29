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

namespace OAuth2Framework\Component\Server\Endpoint\UserInfo\Pairwise;

use Assert\Assertion;
use Base64Url\Base64Url;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountInterface;

final class EncryptedSubjectIdentifier implements PairwiseSubjectIdentifierAlgorithmInterface
{
    /**
     * @var string
     */
    private $pairwiseEncryptionKey;

    /**
     * @var string
     */
    private $algorithm;

    /**
     * @var string
     */
    private $salt;

    /**
     * @var null|string
     */
    private $iv;

    /**
     * EncryptedSubjectIdentifier constructor.
     *
     * @param string      $pairwiseEncryptionKey
     * @param string      $algorithm
     * @param null|string $iv
     * @param string      $salt
     */
    public function __construct(string $pairwiseEncryptionKey, string $algorithm, string $salt, ?string $iv)
    {
        Assertion::inArray($algorithm, openssl_get_cipher_methods(), sprintf('The algorithm \'%s\' is not supported.', $algorithm));
        $this->pairwiseEncryptionKey = $pairwiseEncryptionKey;
        $this->algorithm = $algorithm;
        $this->salt = $salt;
        $this->iv = $iv;
    }

    /**
     * {@inheritdoc}
     */
    public function calculateSubjectIdentifier(UserAccountInterface $userAccount, string $sectorIdentifierHost): string
    {
        $prepared = sprintf(
            '%s:%s:%s',
            $sectorIdentifierHost,
            $userAccount->getPublicId()->getValue(),
            $this->salt
        );

        return Base64Url::encode(openssl_encrypt($prepared, $this->algorithm, $this->pairwiseEncryptionKey, OPENSSL_RAW_DATA, $this->iv));
    }

    /**
     * {@inheritdoc}
     */
    public function getPublicIdFromSubjectIdentifier(string $subjectIdentifier): ?string
    {
        $decoded = openssl_decrypt(Base64Url::decode($subjectIdentifier), $this->algorithm, $this->pairwiseEncryptionKey, OPENSSL_RAW_DATA, $this->iv);
        $parts = explode(':', $decoded);
        if (3 !== count($parts)) {
            return null;
        }

        return $parts[1];
    }
}
