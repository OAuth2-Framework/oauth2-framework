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

final class HashedSubjectIdentifier implements PairwiseSubjectIdentifierAlgorithmInterface
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
     * @var string
     */
    private $salt;

    /**
     * EncryptedSubjectIdentifier constructor.
     *
     * @param string $pairwiseHashKey
     * @param string $algorithm
     * @param string $salt
     */
    public function __construct($pairwiseHashKey, $algorithm, $salt)
    {
        Assertion::string($pairwiseHashKey);
        Assertion::string($algorithm);
        Assertion::string($salt);
        Assertion::inArray($algorithm, hash_algos(), sprintf('The algorithm \'%s\' is not supported.', $algorithm));
        $this->pairwiseHashKey = $pairwiseHashKey;
        $this->algorithm = $algorithm;
        $this->salt = $salt;
    }

    /**
     * {@inheritdoc}
     */
    public function calculateSubjectIdentifier(UserAccountInterface $userAccount, string $sectorIdentifierHost): string
    {
        $prepared = sprintf(
            '%s%s%s',
            $sectorIdentifierHost,
            $userAccount->getPublicId()->getValue(),
            $this->salt
        );

        return Base64Url::encode(hash_hmac($this->algorithm, $prepared, $this->pairwiseHashKey, true));
    }

    /**
     * {@inheritdoc}
     */
    public function getPublicIdFromSubjectIdentifier(string $subjectIdentifier): ? string
    {
    }
}
