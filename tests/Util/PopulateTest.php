<?php

namespace Codeages\Library\Tests\Util;

use Codeages\Library\Util\Populate;
use PHPUnit\Framework\TestCase;

class PopulateTest extends TestCase
{
    public function testExec_Valid_Ok()
    {
        $items = [
            ['id' => 1, 'name' => 'test 1', 'rel_id' => 101,],
            ['id' => 2, 'name' => 'test 2', 'rel_id' => 102,],
        ];

        $populate = new Populate($items);

        $populate
            ->id('rel_id')
            ->find(function($ids) {
                return [
                    ['id' => '101', 'name' => 'rel 101', 'about' => 'rel about 101'],
                    ['id' => '102', 'name' => 'rel 102', 'about' => 'rel about 102'],
                ];
            })
            ->map([
                'rel_name' => 'name',
                'rel_about' => 'about',
            ])
            ->exec();

        $this->assertEquals('rel 101',  $items[0]['rel_name']);
        $this->assertEquals('rel 102',  $items[1]['rel_name']);
        $this->assertEquals('rel about 101',  $items[0]['rel_about']);
        $this->assertEquals('rel about 102',  $items[1]['rel_about']);
    }

    public function testExec_InvalidIdkey_ExceptionThrown()
    {
        $this->expectExceptionMessage('Populate failed, id key `rel_id_invalid` is not in items.');

        $items = [
            ['id' => 1, 'name' => 'test 1', 'rel_id' => 101,],
            ['id' => 2, 'name' => 'test 2', 'rel_id' => 102,],
        ];

        $populate = new Populate($items);

        $populate
            ->id('rel_id_invalid')
            ->find(function($ids) {
                return [
                    ['id' => '101', 'name' => 'rel 101'],
                    ['id' => '102', 'name' => 'rel 102'],
                ];
            })
            ->map(['rel_name' => 'name'])
            ->exec();
    }

    public function testExec3_InvalidMapKey_ExceptionThrown()
    {
        $this->expectExceptionMessage('Populate failed, map key `name_invalid` is not in founded items.');

        $items = [
            ['id' => 1, 'name' => 'test 1', 'rel_id' => 101,],
            ['id' => 2, 'name' => 'test 2', 'rel_id' => 102,],
        ];

        $populate = new Populate($items);

        $populate
            ->id('rel_id')
            ->find(function($ids) {
                return [
                    ['id' => '101', 'name' => 'rel 101'],
                    ['id' => '102', 'name' => 'rel 102'],
                ];
            })
            ->map(['rel_name' => 'name_invalid'])
            ->exec();
    }

}