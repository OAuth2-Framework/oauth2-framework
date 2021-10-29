<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\OpenIdConnect\UserInfo\Pairwise;

use OAuth2Framework\Component\Core\UserAccount\UserAccount;

interface PairwiseSubjectIdentifierAlgorithm
{
    public function calculateSubjectIdentifier(UserAccount $user, string $sectorIdentifierUri): string;

    public function getPublicIdFromSubjectIdentifier(string $subjectIdentifier): ?string;
}
