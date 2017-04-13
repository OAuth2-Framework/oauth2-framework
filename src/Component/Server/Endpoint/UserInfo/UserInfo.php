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

namespace OAuth2Framework\Component\Server\Endpoint\UserInfo;

use Assert\Assertion;
use OAuth2Framework\Component\Server\Endpoint\UserInfo\ClaimSource\ClaimSourceManager;
use OAuth2Framework\Component\Server\Endpoint\UserInfo\Pairwise\PairwiseSubjectIdentifierAlgorithmInterface;
use OAuth2Framework\Component\Server\Endpoint\UserInfo\ScopeSupport\UserInfoScopeSupportManager;
use OAuth2Framework\Component\Server\Model\Client\Client;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountInterface;

final class UserInfo
{
    /**
     * @var null|PairwiseSubjectIdentifierAlgorithmInterface
     */
    private $pairwiseAlgorithm = null;

    /**
     * @var bool
     */
    private $isPairwiseSubjectDefault = false;

    /**
     * @var UserInfoScopeSupportManager
     */
    private $userinfoScopeSupportManager;

    /**
     * @var ClaimSourceManager
     */
    private $claimSourceManager;

    /**
     * UserInfo constructor.
     *
     * @param UserInfoScopeSupportManager $userinfoScopeSupportManager
     * @param ClaimSourceManager          $claimSourceManager
     */
    public function __construct(UserInfoScopeSupportManager $userinfoScopeSupportManager, ClaimSourceManager $claimSourceManager)
    {
        $this->userinfoScopeSupportManager = $userinfoScopeSupportManager;
        $this->claimSourceManager = $claimSourceManager;
    }

    /**
     * @return string[]
     */
    public function getClaimsSupported(): array
    {
        $claimsSupported = [];
        foreach ($this->userinfoScopeSupportManager->all() as $infoScopeSupport) {
            $claimsSupported += $infoScopeSupport->getClaims();
        }

        return array_unique($claimsSupported);
    }

    /**
     * @param Client               $client
     * @param UserAccountInterface $userAccount
     * @param string               $redirectUri
     * @param array                $requestClaims
     * @param string[]             $scopes
     * @param string|null          $claimsLocales
     *
     * @return array
     */
    public function getUserinfo(Client $client, UserAccountInterface $userAccount, string $redirectUri, array $requestClaims, array $scopes, ? string $claimsLocales): array
    {
        $requestClaims = array_merge(
            $this->getClaimsFromClaimScope($scopes),
            $requestClaims
        );
        $claims = $this->getClaimValues($userAccount, $requestClaims, $claimsLocales);
        $claims = array_merge(
            $claims,
            $this->claimSourceManager->getUserInfo($userAccount, $scopes, [])
        );
        $claims['sub'] = $this->calculateSubjectIdentifier($client, $userAccount, $redirectUri);

        return $claims;
    }

    /**
     * @param string[] $scopes
     *
     * @return array
     */
    private function getClaimsFromClaimScope(array $scopes): array
    {
        $result = [];
        foreach ($scopes as $scope) {
            if ($this->userinfoScopeSupportManager->has($scope)) {
                $scope_claims = $this->userinfoScopeSupportManager->get($scope)->getClaims();
                foreach ($scope_claims as $scope_claim) {
                    $result[$scope_claim] = null;
                }
            }
        }

        return $result;
    }

    /**
     * @param UserAccountInterface $userAccount
     * @param string|null          $claimsLocales
     * @param array                $claims
     *
     * @return array
     */
    private function getClaimValues(UserAccountInterface $userAccount, array $claims, ? string $claimsLocales): array
    {
        $result = [];
        if (null === $claimsLocales) {
            $claimsLocales = [];
        } elseif (true === is_string($claimsLocales)) {
            $claimsLocales = explode(' ', $claimsLocales);
        }
        $claimsLocales[] = '';
        foreach ($claims as $claim => $config) {
            foreach ($claimsLocales as $claims_locale) {
                $claim_locale = $this->computeClaimWithLocale($claim, $claims_locale);
                $claim_value = $this->getUserClaim($userAccount, $claim_locale, $config);
                if (null !== $claim_value) {
                    $result[$claim_locale] = $claim_value;
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * @param string $claim
     * @param string $locale
     *
     * @return string
     */
    protected function computeClaimWithLocale($claim, $locale): string
    {
        if (empty($locale)) {
            return $claim;
        }

        return sprintf('%s#%s', $claim, $locale);
    }

    /**
     * @param UserAccountInterface $userAccount
     * @param string               $claim
     * @param string               $claim
     * @param null|array           $config
     *
     * @return null|mixed
     */
    protected function getUserClaim(UserAccountInterface $userAccount, $claim, $config)
    {
        //The parameter $config is not yet used and the claim is returned as-is whatever the client requested
        //To be fixed
        if ($userAccount->has($claim)) {
            return $userAccount->get($claim);
        }
    }

    /**
     * @param PairwiseSubjectIdentifierAlgorithmInterface $pairwiseAlgorithm
     * @param bool                                        $isPairwiseSubjectDefault
     */
    public function enablePairwiseSubject(PairwiseSubjectIdentifierAlgorithmInterface $pairwiseAlgorithm, bool $isPairwiseSubjectDefault)
    {
        $this->pairwiseAlgorithm = $pairwiseAlgorithm;
        $this->isPairwiseSubjectDefault = $isPairwiseSubjectDefault;
    }

    /**
     * @return bool
     */
    public function isPairwiseSubjectIdentifierSupported(): bool
    {
        return null !== $this->pairwiseAlgorithm;
    }

    /**
     * @return PairwiseSubjectIdentifierAlgorithmInterface|null
     */
    public function getPairwiseSubjectIdentifierAlgorithm(): ? PairwiseSubjectIdentifierAlgorithmInterface
    {
        return $this->pairwiseAlgorithm;
    }

    /**
     * @return bool
     */
    public function isPairwiseSubjectDefault(): bool
    {
        return $this->isPairwiseSubjectDefault;
    }

    /**
     * @param Client               $client
     * @param UserAccountInterface $userAccount
     * @param string               $redirectUri
     *
     * @return string
     */
    protected function calculateSubjectIdentifier(Client $client, UserAccountInterface $userAccount, string $redirectUri): string
    {
        $sub = $userAccount->getPublicId()->getValue();
        if (false === $this->isPairwiseSubjectIdentifierSupported()) {
            return $sub;
        }
        if (($client->has('subject_type') && ('pairwise' === $client->get('subject_type')) || true === $this->isPairwiseSubjectDefault())) {
            $sectorIdentifierHost = $this->getSectorIdentifierHost($client, $redirectUri);

            return $this->pairwiseAlgorithm->calculateSubjectIdentifier(
                $userAccount,
                $sectorIdentifierHost
            );
        }

        return $sub;
    }

    /**
     * @param Client $client
     * @param string $redirectUri
     *
     * @return string
     */
    private function getSectorIdentifierHost(Client $client, string $redirectUri): string
    {
        $uri = $redirectUri;

        if (true === $client->has('sector_identifier_uri')) {
            $uri = $client->get('sector_identifier_uri');
        }

        $data = parse_url($uri);
        Assertion::true(is_array($data) && array_key_exists('host', $data), sprintf('Invalid Sector Identifier Uri \'%s\'.', $uri));

        return $data['host'];
    }
}
