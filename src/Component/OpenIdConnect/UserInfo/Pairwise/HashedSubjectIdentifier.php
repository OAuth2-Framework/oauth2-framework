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

class HashedSubjectIdentifier implements PairwiseSubjectIdentifierAlgorithm
{
    /**
     * @var string
     */
    private $algorithm;

    /**
     * @var string
     */
    private $pairwiseHashKey;

    /**
     * EncryptedSubjectIdentifier constructor.
     *
     * @param string $pairwiseHashKey
     * @param string $algorithm
     */
    public function __construct(string $pairwiseHashKey, string $algorithm)
    {
        if (!in_array($algorithm, openssl_get_cipher_methods())) {
            throw new \InvalidArgumentException(sprintf('The algorithm "%s" is not supported.', $algorithm));
        }
        $this->pairwiseHashKey = $pairwiseHashKey;
        $this->algorithm = $algorithm;
    }

    /**
     * {@inheritdoc}
     */
    public function calculateSubjectIdentifier(UserAccount $userAccount, string $sectorIdentifierHost): string
    {
        $prepared = sprintf(
            '%s%s',
            $sectorIdentifierHost,
            $userAccount->getPublicId()->getValue()
        );

        return Base64Url::encode(hash_hmac($this->algorithm, $prepared, $this->pairwiseHashKey, true));
    }

    /**
     * {@inheritdoc}
     */
    public function getPublicIdFromSubjectIdentifier(string $subjectIdentifier): ?string
    {
        return null;
    }
}
