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

namespace OAuth2Framework\Component\Server\Endpoint\Authorization\UserAccountDiscovery;

use Assert\Assertion;
use OAuth2Framework\Component\Server\Endpoint\Authorization\Authorization;
use OAuth2Framework\Component\Server\Endpoint\Authorization\Exception\CreateRedirectionException;
use OAuth2Framework\Component\Server\Endpoint\Authorization\Exception\RedirectToLoginPageException;
use OAuth2Framework\Component\Server\Endpoint\UserInfo\Pairwise\PairwiseSubjectIdentifierAlgorithmInterface;
use OAuth2Framework\Component\Server\Model\IdToken\IdToken;
use OAuth2Framework\Component\Server\Model\IdToken\IdTokenId;
use OAuth2Framework\Component\Server\Model\IdToken\IdTokenLoader;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountRepositoryInterface;
use OAuth2Framework\Component\Server\Response\OAuth2ResponseFactoryManager;
use Psr\Http\Message\ServerRequestInterface;

final class IdTokenHintDiscovery implements UserAccountDiscoveryInterface
{
    /**
     * @var IdTokenLoader
     */
    private $idTokenLoader;

    /**
     * @var PairwiseSubjectIdentifierAlgorithmInterface|null
     */
    private $pairwiseAlgorithm = null;

    /**
     * @var UserAccountRepositoryInterface
     */
    private $userAccountRepository;

    /**
     * IdTokenHintExtension constructor.
     *
     * @param IdTokenLoader                  $idTokenLoader
     * @param UserAccountRepositoryInterface $userAccountRepository
     */
    public function __construct(IdTokenLoader $idTokenLoader, UserAccountRepositoryInterface $userAccountRepository)
    {
        $this->idTokenLoader = $idTokenLoader;
        $this->userAccountRepository = $userAccountRepository;
    }

    /**
     * @param PairwiseSubjectIdentifierAlgorithmInterface $pairwiseAlgorithm
     */
    public function enablePairwiseSubject(PairwiseSubjectIdentifierAlgorithmInterface $pairwiseAlgorithm)
    {
        $this->pairwiseAlgorithm = $pairwiseAlgorithm;
    }

    /**
     * {@inheritdoc}
     */
    public function find(ServerRequestInterface $request, Authorization $authorization, callable $next): Authorization
    {
        if ($authorization->hasQueryParam('id_token_hint')) {
            try {
                $idTokenId = IdTokenId::create($authorization->getQueryParam('id_token_hint'));
                $idToken = $this->idTokenLoader->load($idTokenId);
                Assertion::isInstanceOf($idToken, IdToken::class, 'The parameter \'id_token_hint\' does not contain a valid ID Token.');
                $userAccountId = $idToken->getUserAccountId();
                if (null !== $this->pairwiseAlgorithm) {
                    $publicId = $this->pairwiseAlgorithm->getPublicIdFromSubjectIdentifier($userAccountId->getValue());
                    Assertion::notNull($publicId, 'Unable to retrieve the user account using the \'id_token_hint\' parameter.');
                } else {
                    $publicId = $userAccountId->getValue();
                }
                $realUserAccountId = UserAccountId::create($publicId);

                $tmp = $this->userAccountRepository->findUserAccount($realUserAccountId);
                if (null !== $tmp) {
                    if (null !== $authorization->getUserAccount()) {
                        if ($tmp->getPublicId()->getValue() !== $authorization->getUserAccount()->getPublicId()->getValue()) {
                            throw new RedirectToLoginPageException($authorization);
                        }
                    } else {
                        $authorization = $authorization->withUserAccount($tmp, false);
                    }
                }
            } catch (\InvalidArgumentException $e) {
                throw new CreateRedirectionException($authorization, OAuth2ResponseFactoryManager::ERROR_INVALID_REQUEST, $e->getMessage());
            }
        }

        return $next($request, $authorization);
    }
}
