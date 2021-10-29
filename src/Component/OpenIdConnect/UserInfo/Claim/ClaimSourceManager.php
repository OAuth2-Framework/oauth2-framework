<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\OpenIdConnect\UserInfo\Claim;

use function count;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;

class ClaimSourceManager
{
    /**
     * @var ClaimSource[]
     */
    private array $claimSources = [];

    public function add(ClaimSource $claimSource): void
    {
        $this->claimSources[] = $claimSource;
    }

    /**
     * @return ClaimSource[]
     */
    public function all(): array
    {
        return $this->claimSources;
    }

    public function getUserInfo(UserAccount $userAccount, string $scope, array $previousClaims): array
    {
        $scopes = $scope === '' ? [] : explode(' ', $scope);
        $claims = [
            '_claim_names' => [],
            '_claim_sources' => [],
        ];
        $i = 0;

        foreach ($this->all() as $claimSource) {
            $result = $claimSource->getUserInfo($userAccount, $scopes, $previousClaims);
            if ($result !== null) {
                ++$i;
                $src = sprintf('src%d', $i);
                $_claim_names = [];
                foreach ($result->getAvailableClaims() as $claim) {
                    if ($claim !== 'sub') {
                        $_claim_names[$claim] = $src;
                    }
                }
                $claims['_claim_names'] = array_merge($claims['_claim_names'], $_claim_names);
                $claims['_claim_sources'][$src] = $result->getSource();
            }
        }

        return count($claims['_claim_names']) === 0 ? [] : $previousClaims;
    }
}
