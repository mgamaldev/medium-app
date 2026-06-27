<?php

namespace Tests\Unit\ValueObjects;

use App\ValueObjects\ReadTime;
use PHPUnit\Framework\TestCase;

class ReadTimeTest extends TestCase
{
    public function test_it_can_be_instantiated_with_valid_reading_time()
    {
        $readTime = 5;

        $result = new ReadTime($readTime);

        $this->assertInstanceOf(ReadTime::class, $result);
    }

    public function test_it_throws_exception_if_reading_time_is_zero()
    {
        $readTime = 0;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("read time shouldn't be equal 0");

        $result = new ReadTime(($readTime));

        $this->assertInstanceOf(ReadTime::class, $result);

    }

    public function test_it_throws_exception_if_reading_time_is_negative()
    {
        $readTime = -2;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("read time shouldn't be less than 0");

        $result = new ReadTime(($readTime));

        $this->assertInstanceOf(ReadTime::class, $result);

    }
}
