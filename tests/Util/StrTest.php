<?php

namespace Codeages\Library\Tests\Util;

use Codeages\Library\Util\Str;
use PHPUnit\Framework\TestCase;

class StrTest extends TestCase
{
    public function testRandom()
    {
        $str = Str::random(1);
        $this->assertEquals(1, strlen($str));

        $str = Str::random(16);
        $this->assertEquals(16, strlen($str));
    }

}