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

namespace OAuth2Framework\Bundle\Tests\TestBundle\Entity;

use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCode;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeId;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeRepository;
use Symfony\Component\Cache\Adapter\AdapterInterface;

class AuthCodeRepository implements AuthorizationCodeRepository
{
    /**
     * @var AdapterInterface
     */
    private $cache;

    /**
     * AuthCodeRepository constructor.
     *
     * @param AdapterInterface $cache
     */
    public function __construct(AdapterInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function find(AuthorizationCodeId $authCodeId): ? AuthorizationCode
    {
        $authCode = $this->getFromCache($authCodeId);

        return $authCode;
    }

    /**
     * @param AuthorizationCode $authCode
     */
    public function save(AuthorizationCode $authCode)
    {
        $authCode->eraseMessages();
        $this->cacheObject($authCode);
    }

    /**
     * @param AuthorizationCodeId $authCodeId
     *
     * @return AuthorizationCode|null
     */
    private function getFromCache(AuthorizationCodeId $authCodeId): ? AuthorizationCode
    {
        $itemKey = sprintf('oauth2-auth_code-%s', $authCodeId->getValue());
        $item = $this->cache->getItem($itemKey);
        if ($item->isHit()) {
            return $item->get();
        }

        return null;
    }

    /**
     * @param AuthorizationCode $authCode
     */
    private function cacheObject(AuthorizationCode $authCode)
    {
        $itemKey = sprintf('oauth2-auth_code-%s', $authCode->getTokenId()->getValue());
        $item = $this->cache->getItem($itemKey);
        $item->set($authCode);
        $item->tag(['oauth2_server', 'auth_code', $itemKey]);
        $this->cache->save($item);
    }
}
