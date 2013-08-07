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

namespace Metzli\Encoder\DataEncoder;

use Metzli\Utils\BitArray;

abstract class Token
{
    private $previous;
    private $totalBitCount;

    public function __construct(Token $previous = null, $totalBitCount)
    {
        $this->previous = $previous;
        $this->totalBitCount = $totalBitCount;
    }

    final public function getPrevious()
    {
        return $this->previous;
    }

    final public function getTotalBitCount()
    {
        return $this->totalBitCount;
    }

    final public function add($value, $bitCount)
    {
        return new SimpleToken($this, $this->totalBitCount + $bitCount, $value, $bitCount);
    }

    final public function addBinaryShift($start, $byteCount)
    {
        $bitCount = ($byteCount * 8);
        if ($byteCount <= 31) {
            $bitCount += 10;
        } elseif ($byteCount <= 62) {
            $bitCount += 20;
        } else {
            $bitCount += 21;
        }

        return new BinaryShiftToken($this, $this->totalBitCount + $bitCount, $start, $byteCount);
    }

    final public static function createEmpty()
    {
        return new SimpleToken(null, 0, 0, 0);
    }

    abstract public function appendTo(BitArray $bitArray, array $text);
}
