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

namespace OAuth2Framework\Bundle\Server\Tests\TestBundle\Service;

use OAuth2Framework\Component\Server\Model\Event\Event;
use OAuth2Framework\Component\Server\Model\Event\EventStoreInterface;
use OAuth2Framework\Component\Server\Model\Id\Id;
use OAuth2Framework\Component\Server\Schema\DomainConverter;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

final class EventStore implements EventStoreInterface
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
        $this->domainConverter = new DomainConverter();
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
    public function getEvents(Id $id): array
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
