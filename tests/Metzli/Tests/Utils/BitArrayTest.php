<?php

/*
 * Copyright 2013 Metzli and ZXing authors
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Metzli\Tests\Utils;

use Metzli\Tests\TestCase;
use Metzli\Utils\BitArray;

class BitArrayTest extends TestCase
{
    public function testGetSet()
    {
        $array = new BitArray(33);
        for ($i = 0; $i < 33; $i++) {
            $this->assertEquals(0, $array->get($i));
            $array->set($i);
            $this->assertEquals(1, $array->get($i));
        }
    }

    public function testClear()
    {
        $array = new BitArray(33);
        for ($i = 0; $i < 33; $i++) {
            $array->set($i);
        }
        $array->clear();
        for ($i = 0; $i < 33; $i++) {
            $this->assertEquals(0, $array->get($i));
        }
    }

    public function testResize()
    {
        $array = new BitArray(32);
        $array->set(20);

        $array->resize(5);
        $this->assertEquals(5, $array->getLength());

        $array->resize(32);
        $this->assertEquals(32, $array->getLength());
        $this->assertEquals(0, $array->get(20));
    }

    public function testAsArray()
    {
        $array = new BitArray(64);
        $array->set(0);
        $array->set(63);
        $ints = $array->asArray();
        $this->assertEquals(1, $ints[0]);
        $this->assertEquals(0, $ints[1]);
    }

    public function testTryGet()
    {
        $array = new BitArray(5);
        $this->assertEquals(0, $array->tryGet(6));
    }

    public function testAppend()
    {
        $array = new BitArray(2);
        $array->append(0x7, 3);
        $this->assertEquals(5, $array->getLength());
        $this->assertEquals(array(0, 0, 1, 1, 1), $array->asArray());
    }

    public function testAppendBytes()
    {
        $array = new BitArray(2);
        $array->appendBytes('AB');
        $this->assertEquals(array(0, 0, 0, 1, 0, 0, 0, 0, 0, 1, 0, 1, 0, 0, 0, 0, 1, 0), $array->asArray());
    }
}
