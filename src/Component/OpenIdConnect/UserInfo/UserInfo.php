<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\OpenIdConnect\UserInfo;

use function array_key_exists;
use InvalidArgumentException;
use function is_array;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\Claim\ClaimManager;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\Pairwise\PairwiseSubjectIdentifierAlgorithm;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\ScopeSupport\UserInfoScopeSupportManager;

class UserInfo
{
    private ?PairwiseSubjectIdentifierAlgorithm $pairwiseAlgorithm = null;

    public function __construct(
        private UserInfoScopeSupportManager $userinfoScopeSupportManager,
        private ClaimManager $claimManager
    ) {
    }

    public function getUserinfo(
        Client $client,
        UserAccount $userAccount,
        string $redirectUri,
        array $requestedClaims,
        ?string $scope,
        ?string $claimsLocales
    ): array {
        $requestedClaims = array_merge($this->getClaimsFromClaimScope($scope), $requestedClaims);
        $claims = $this->getClaimValues($userAccount, $requestedClaims, $claimsLocales);
        /*$claims = array_merge(
            $claims,
            $this->claimSourceManager->getUserInfo($userAccount, $scope, [])
        );*/
        $claims['sub'] = $this->calculateSubjectIdentifier($client, $userAccount, $redirectUri);

        return $claims;
    }

    public function enablePairwiseSubject(PairwiseSubjectIdentifierAlgorithm $pairwiseAlgorithm): self
    {
        $this->pairwiseAlgorithm = $pairwiseAlgorithm;

        return $this;
    }

    public function isPairwiseSubjectIdentifierSupported(): bool
    {
        return $this->pairwiseAlgorithm !== null;
    }

    public function getPairwiseSubjectIdentifierAlgorithm(): ?PairwiseSubjectIdentifierAlgorithm
    {
        return $this->pairwiseAlgorithm;
    }

    private function getClaimsFromClaimScope(?string $scope): array
    {
        $result = [];
        $scope = $scope ?? '';

        foreach (explode(' ', $scope) as $scp) {
            if ($this->userinfoScopeSupportManager->has($scp)) {
                $scope_claims = $this->userinfoScopeSupportManager->get($scp)
                    ->getAssociatedClaims()
                ;
                foreach ($scope_claims as $scope_claim) {
                    $result[$scope_claim] = null;
                }
            }
        }

        return $result;
    }

    private function getClaimValues(UserAccount $userAccount, array $requestedClaims, ?string $claimsLocales): array
    {
        $claimsLocales = $claimsLocales === null ? [] : array_unique(explode(' ', $claimsLocales));

        return $this->claimManager->getUserInfo($userAccount, $requestedClaims, $claimsLocales);
        /*foreach ($requestedClaims as $claim => $config) {
            foreach ($claimsLocales as $claims_locale) {
                $claim_locale = $this->computeClaimWithLocale($claim, $claims_locale);
                $claim_value = $this->getUserClaim($userAccount, $claim_locale, $config);
                if (null !== $claim_value) {
                    $result[$claim_locale] = $claim_value;

                    break;
                }
            }
        }*/
    }

    private function calculateSubjectIdentifier(Client $client, UserAccount $userAccount, string $redirectUri): string
    {
        $sub = $userAccount->getUserAccountId()
            ->getValue()
        ;
        if ($this->pairwiseAlgorithm === null) {
            return $sub;
        }
        if ($client->has('subject_type') && ($client->get('subject_type') === 'pairwise')) {
            $sectorIdentifierHost = $this->getSectorIdentifierHost($client, $redirectUri);

            return $this->pairwiseAlgorithm->calculateSubjectIdentifier($userAccount, $sectorIdentifierHost);
        }

        return $sub;
    }

    private function getSectorIdentifierHost(Client $client, string $redirectUri): string
    {
        $uri = $redirectUri;

        if ($client->has('sector_identifier_uri') === true) {
            $uri = $client->get('sector_identifier_uri');
        }

        $data = parse_url($uri);
        if (! is_array($data) || ! array_key_exists('host', $data)) {
            throw new InvalidArgumentException(sprintf('Invalid Sector Identifier Uri "%s".', $uri));
        }

        return $data['host'];
    }
}
