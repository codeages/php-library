<?php

namespace Codeages\Library\Tests;

use PHPUnit\Framework\TestCase;
use function Codeages\Library\populate;

class FunctionTest extends TestCase
{
    public function testPopulate()
    {
        $items = [
            ['id' => 1, 'name' => 'test 1', 'rel_id' => 101,],
            ['id' => 2, 'name' => 'test 2', 'rel_id' => 102,],
        ];

        populate($items)
            ->id('rel_id')
            ->find(function($ids) {
                return [
                    ['id' => '101', 'name' => 'rel 101'],
                    ['id' => '102', 'name' => 'rel 102'],
                ];
            })
            ->map(['rel_name' => 'name'])
            ->exec();

        $this->assertEquals('rel 101',  $items[0]['rel_name']);
        $this->assertEquals('rel 102',  $items[1]['rel_name']);
    }
}
