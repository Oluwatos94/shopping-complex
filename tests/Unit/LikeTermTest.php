<?php

declare(strict_types=1);

namespace Tests\Unit;

use ModulesShoppingComplex\Shared\Support\LikeTerm;
use PHPUnit\Framework\TestCase;

class LikeTermTest extends TestCase
{
    public function test_an_ordinary_term_is_untouched(): void
    {
        $this->assertSame('Ada Fabrics', LikeTerm::escape('Ada Fabrics'));
        $this->assertSame('', LikeTerm::escape(''));
    }

    public function test_wildcards_are_neutralised(): void
    {
        $this->assertSame('50\%', LikeTerm::escape('50%'));
        $this->assertSame('a\_b', LikeTerm::escape('a_b'));
        $this->assertSame('\%\%\%', LikeTerm::escape('%%%'));
    }

    /**
     * Escaping the backslash last would leave "\%" as a literal backslash
     * followed by a live wildcard.
     */
    public function test_a_backslash_cannot_re_arm_a_wildcard(): void
    {
        $this->assertSame('\\\\\%', LikeTerm::escape('\%'));
        $this->assertSame('c:\\\\temp', LikeTerm::escape('c:\temp'));
    }
}
