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

use Base64Url\Base64Url;
use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenRepository;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use Symfony\Component\Cache\Adapter\AdapterInterface;

class AccessTokenByReferenceRepository implements AccessTokenRepository
{
    /**
     * @var int
     */
    private $lifetime;

    /**
     * @var int
     */
    private $minLength;

    /**
     * @var int
     */
    private $maxLength;

    /**
     * @var AdapterInterface
     */
    private $cache;

    /**
     * AccessTokenByReferenceRepository constructor.
     *
     * @param int              $minLength
     * @param int              $maxLength
     * @param int              $lifetime
     * @param AdapterInterface $cache
     */
    public function __construct(int $minLength, int $maxLength, int $lifetime, AdapterInterface $cache)
    {
        $this->minLength = $minLength;
        $this->maxLength = $maxLength;
        $this->lifetime = $lifetime;
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function find(AccessTokenId $accessTokenId)
    {
        $accessToken = $this->getFromCache($accessTokenId);

        return $accessToken;
    }

    /**
     * @param AccessToken $accessToken
     */
    public function save(AccessToken $accessToken)
    {
        $accessToken->eraseMessages();
        $this->cacheObject($accessToken);
    }

    /**
     * {@inheritdoc}
     */
    public function create(ResourceOwnerId $resourceOwnerId, ClientId $clientId, DataBag $parameters, DataBag $metadatas, ? ResourceServerId $resourceServerId): AccessToken
    {
        $expiresAt = new \DateTimeImmutable(sprintf('now +%u seconds', $this->lifetime));
        $length = random_int($this->minLength, $this->maxLength);
        $accessTokenId = AccessTokenId::create(Base64Url::encode(random_bytes($length)));
        $accessToken = AccessToken::createEmpty();
        $accessToken = $accessToken->create($accessTokenId, $resourceOwnerId, $clientId, $parameters, $metadatas, $expiresAt, $resourceServerId);

        return $accessToken;
    }

    /**
     * @param AccessTokenId $accessTokenId
     *
     * @return AccessToken|null
     */
    private function getFromCache(AccessTokenId $accessTokenId): ? AccessToken
    {
        $itemKey = sprintf('oauth2-access_token-%s', $accessTokenId->getValue());
        $item = $this->cache->getItem($itemKey);
        if ($item->isHit()) {
            return $item->get();
        }

        return null;
    }

    /**
     * @param AccessToken $accessToken
     */
    private function cacheObject(AccessToken $accessToken)
    {
        $itemKey = sprintf('oauth2-access_token-%s', $accessToken->getTokenId()->getValue());
        $item = $this->cache->getItem($itemKey);
        $item->set($accessToken);
        $item->tag(['oauth2_server', 'access_token', $itemKey]);
        $this->cache->save($item);
    }
}
