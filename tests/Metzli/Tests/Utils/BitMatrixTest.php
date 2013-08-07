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

use Metzli\Utils\BitMatrix;
use Metzli\Tests\TestCase;

class BitMatrixTest extends TestCase
{
    public function testGetSet()
    {
        $matrix = new BitMatrix(33);
        $this->assertEquals(33, $matrix->getHeight());

        for ($y = 0; $y < 33; $y++) {
            for ($x = 0; $x < 33; $x++) {
                if ($y * $x % 3 == 0) {
                    $matrix->set($x, $y);
                }
            }
        }
        for ($y = 0; $y < 33; $y++) {
            for ($x = 0; $x < 33; $x++) {
                $this->assertEquals(($y * $x % 3 == 0), $matrix->get($x, $y));
            }
        }
    }

    public function testRectangularMatrix()
    {
        $matrix = new BitMatrix(75, 20);
        $this->assertEquals(75, $matrix->getWidth());
        $this->assertEquals(20, $matrix->getHeight());

        $matrix->set(10, 0);
        $matrix->set(11, 1);
        $matrix->set(50, 2);
        $matrix->set(51, 3);
        $matrix->flip(74, 4);
        $matrix->flip(0, 5);
        $this->assertEquals(1, $matrix->get(10, 0));
        $this->assertEquals(1, $matrix->get(11, 1));
        $this->assertEquals(1, $matrix->get(50, 2));
        $this->assertEquals(1, $matrix->get(51, 3));
        $this->assertEquals(1, $matrix->get(74, 4));
        $this->assertEquals(1, $matrix->get(0, 5));

        $matrix->flip(50, 2);
        $matrix->flip(51, 3);
        $this->assertEquals(0, $matrix->get(50, 2));
        $this->assertEquals(0, $matrix->get(51, 3));
    }

    public function testClear()
    {
        $matrix = new BitMatrix(5, 10);
        $matrix->set(3, 4);
        $matrix->clear();
        $this->assertEquals(0, $matrix->get(3, 4));
    }
}
