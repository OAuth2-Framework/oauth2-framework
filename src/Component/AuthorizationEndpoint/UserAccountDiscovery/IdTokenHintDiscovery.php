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

namespace OAuth2Framework\Component\AuthorizationEndpoint\UserAccountDiscovery;

use OAuth2Framework\Component\AuthorizationEndpoint\Authorization;
use OAuth2Framework\Component\AuthorizationEndpoint\Exception\CreateRedirectionException;
use OAuth2Framework\Component\Core\Exception\OAuth2Exception;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountRepository;
use OAuth2Framework\Component\OpenIdConnect\IdToken;
use OAuth2Framework\Component\OpenIdConnect\IdTokenId;
use OAuth2Framework\Component\OpenIdConnect\IdTokenLoader;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\Pairwise\PairwiseSubjectIdentifierAlgorithm;

class IdTokenHintDiscovery implements UserAccountDiscovery
{
    /**
     * @var IdTokenLoader
     */
    private $idTokenLoader;

    /**
     * @var PairwiseSubjectIdentifierAlgorithm|null
     */
    private $pairwiseAlgorithm = null;

    /**
     * @var UserAccountRepository
     */
    private $userAccountRepository;

    /**
     * IdTokenHintExtension constructor.
     *
     * @param IdTokenLoader         $idTokenLoader
     * @param UserAccountRepository $userAccountRepository
     */
    public function __construct(IdTokenLoader $idTokenLoader, UserAccountRepository $userAccountRepository)
    {
        $this->idTokenLoader = $idTokenLoader;
        $this->userAccountRepository = $userAccountRepository;
    }

    /**
     * @param PairwiseSubjectIdentifierAlgorithm $pairwiseAlgorithm
     */
    public function enablePairwiseSubject(PairwiseSubjectIdentifierAlgorithm $pairwiseAlgorithm)
    {
        $this->pairwiseAlgorithm = $pairwiseAlgorithm;
    }

    /**
     * {@inheritdoc}
     */
    public function find(Authorization $authorization, ?bool &$isFullyAuthenticated = null): ?UserAccount
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function check(Authorization $authorization)
    {
        if ($authorization->hasQueryParam('id_token_hint')) {
            try {
                $idTokenId = IdTokenId::create($authorization->getQueryParam('id_token_hint'));
                $idToken = $this->idTokenLoader->load($idTokenId);
                if (!$idToken instanceof IdToken) {
                    throw new \InvalidArgumentException('The parameter "id_token_hint" does not contain a valid ID Token.');
                }
                $userAccountId = $idToken->getUserAccountId();
                if (null !== $this->pairwiseAlgorithm) {
                    $publicId = $this->pairwiseAlgorithm->getPublicIdFromSubjectIdentifier($userAccountId->getValue());
                    if (null === $publicId) {
                        throw new \InvalidArgumentException('Unable to retrieve the user account using the "id_token_hint" parameter.');
                    }
                } else {
                    $publicId = $userAccountId->getValue();
                }
                $realUserAccountId = UserAccountId::create($publicId);

                $userAccount = $this->userAccountRepository->find($realUserAccountId);
                if (null === $userAccount) {
                    throw new \InvalidArgumentException('Unable to retrieve the user account using the "id_token_hint" parameter.');
                }
                if (null !== $userAccount && $userAccount->getPublicId()->getValue() !== $authorization->getUserAccount()->getPublicId()->getValue()) {
                    throw new \InvalidArgumentException('Unable to retrieve the user account using the "id_token_hint" parameter.');
                }
            } catch (\InvalidArgumentException $e) {
                throw new CreateRedirectionException($authorization, OAuth2Exception::ERROR_INVALID_REQUEST, $e->getMessage());
            }
        }
    }
}
