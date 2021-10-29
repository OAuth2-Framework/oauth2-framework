<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\Core\DataBag;

use OAuth2Framework\Component\Core\DataBag\DataBag;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class DataBagTest extends TestCase
{
    /**
     * @test
     */
    public function iCanCreateADataBag(): void
    {
        $data = new DataBag([
            'foo' => 'bar',
        ]);
        $data->set('foo', 'BAR');

        static::assertInstanceOf(DataBag::class, $data);
        static::assertTrue($data->has('foo'));
        static::assertFalse($data->has('---'));
    }
}
