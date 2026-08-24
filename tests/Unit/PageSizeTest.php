<?php

declare(strict_types=1);

namespace Tests\Unit;

use ModulesShoppingComplex\Shared\Pagination\PageSize;
use PHPUnit\Framework\TestCase;

class PageSizeTest extends TestCase
{
    public function test_a_usable_size_is_kept(): void
    {
        $this->assertSame(15, PageSize::resolve(15));
        $this->assertSame(1, PageSize::resolve(1));
        $this->assertSame(7, PageSize::resolve('7'));
    }

    public function test_an_oversized_request_is_capped(): void
    {
        $this->assertSame(50, PageSize::resolve(5000));
        $this->assertSame(100, PageSize::resolve(5000, max: 100));
    }

    /**
     * A negative size reaches limit(), which Laravel drops entirely — the whole
     * table would come back in one response.
     */
    public function test_a_size_below_one_is_raised_to_one(): void
    {
        foreach ([0, '0', -1, -5000] as $requested) {
            $this->assertSame(1, PageSize::resolve($requested));
        }
    }

    public function test_an_unusable_request_falls_back_to_the_default(): void
    {
        foreach ([null, '', 'abc', []] as $requested) {
            $this->assertSame(20, PageSize::resolve($requested));
        }

        $this->assertSame(50, PageSize::resolve(null, 50));
    }
}
