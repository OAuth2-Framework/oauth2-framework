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

namespace OAuth2Framework\Component\OpenIdConnect\UserInfo\Pairwise;

use Base64Url\Base64Url;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;

class EncryptedSubjectIdentifier implements PairwiseSubjectIdentifierAlgorithm
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
     * EncryptedSubjectIdentifier constructor.
     *
     * @param string $pairwiseEncryptionKey
     * @param string $algorithm
     */
    public function __construct(string $pairwiseEncryptionKey, string $algorithm)
    {
        if (!in_array($algorithm, openssl_get_cipher_methods())) {
            throw new \InvalidArgumentException(sprintf('The algorithm "%s" is not supported.', $algorithm));
        }
        $this->pairwiseEncryptionKey = $pairwiseEncryptionKey;
        $this->algorithm = $algorithm;
    }

    /**
     * {@inheritdoc}
     */
    public function calculateSubjectIdentifier(UserAccount $userAccount, string $sectorIdentifierHost): string
    {
        $prepared = sprintf(
            '%s:%s',
            $sectorIdentifierHost,
            $userAccount->getPublicId()->getValue()
        );

        return Base64Url::encode(openssl_encrypt($prepared, $this->algorithm, $this->pairwiseEncryptionKey, OPENSSL_RAW_DATA));
    }

    /**
     * {@inheritdoc}
     */
    public function getPublicIdFromSubjectIdentifier(string $subjectIdentifier): ? string
    {
        $decoded = openssl_decrypt(Base64Url::decode($subjectIdentifier), $this->algorithm, $this->pairwiseEncryptionKey, OPENSSL_RAW_DATA);
        $parts = explode(':', $decoded);
        if (3 !== count($parts)) {
            return null;
        }

        return $parts[1];
    }
}
