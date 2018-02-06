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

namespace OAuth2Framework\Bundle\Tests\TestBundle\Service;

use OAuth2Framework\Component\Core\Domain\DomainConverter;
use OAuth2Framework\Component\Core\Domain\DomainUriLoader;
use OAuth2Framework\Component\Core\Event\Event;
use OAuth2Framework\Component\Core\Id\Id;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class EventStore implements \OAuth2Framework\Component\Core\Event\EventStore
{
    private const STORAGE_PATH = '%s/fixtures/%s/';

    private const EVENT_PATH = '%s/%s/events/';

    private const EVENT_FILENAME = '%s/%s/events/%s.json';

    /**
     * @var DomainConverter
     */
    private $domainConverter;

    /**
     * @var string
     */
    private $storagePath;

    /**
     * EventStore constructor.
     *
     * @param string $storagePath
     * @param string $folder
     */
    public function __construct(string $storagePath, string $folder)
    {
        $this->storagePath = sprintf(self::STORAGE_PATH, $storagePath, $folder);
        $this->domainConverter = new DomainConverter(
            new DomainUriLoader()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function save(Event $event)
    {
        $dirname = sprintf(self::EVENT_PATH, $this->storagePath, $event->getDomainId()->getValue());
        $fs = new Filesystem();
        if (!$fs->exists($dirname)) {
            $fs->mkdir($dirname);
        }
        $json = $this->domainConverter->toJson($event);
        $filename = sprintf(self::EVENT_FILENAME, $this->storagePath, $event->getDomainId()->getValue(), $event->getEventId()->getValue());
        file_put_contents($filename, $json);
    }

    /**
     * {@inheritdoc}
     */
    public function findAllForDomainId(Id $id): array
    {
        $dirname = sprintf(self::EVENT_PATH, $this->storagePath, $id->getValue());
        $fs = new Filesystem();
        if (!$fs->exists($dirname)) {
            return [];
        }
        $finder = new Finder();
        $finder->files()->in($dirname);

        $events = [];
        foreach ($finder as $file) {
            $json = $file->getContents();
            $events[] = $this->domainConverter->fromJson($json);
        }

        return $events;
    }
}
