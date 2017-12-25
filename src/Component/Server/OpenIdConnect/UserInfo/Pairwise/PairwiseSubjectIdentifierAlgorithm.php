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

namespace OAuth2Framework\Component\Server\OpenIdConnect\UserInfo\Pairwise;

use OAuth2Framework\Component\Server\Core\UserAccount\UserAccount;

interface PairwiseSubjectIdentifierAlgorithm
{
    /**
     * @param UserAccount $user
     * @param string      $sectorIdentifierUri
     *
     * @return string
     */
    public function calculateSubjectIdentifier(UserAccount $user, string $sectorIdentifierUri): string;

    /**
     * @param string $subjectIdentifier
     *
     * @return string|null
     */
    public function getPublicIdFromSubjectIdentifier(string $subjectIdentifier): ?string;
}
