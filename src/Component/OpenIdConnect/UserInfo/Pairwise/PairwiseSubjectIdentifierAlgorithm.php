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

use OAuth2Framework\Component\Core\UserAccount\UserAccount;

interface PairwiseSubjectIdentifierAlgorithm
{
    public function calculateSubjectIdentifier(UserAccount $user, string $sectorIdentifierUri): string;

    public function getPublicIdFromSubjectIdentifier(string $subjectIdentifier): ?string;
}
