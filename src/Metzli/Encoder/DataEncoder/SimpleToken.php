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

class SimpleToken extends Token
{
    private $value;
    private $bitCount;

    public function __construct(Token $previous = null, $totalBitCount, $value, $bitCount)
    {
        parent::__construct($previous, $totalBitCount);
        $this->value = $value;
        $this->bitCount = $bitCount;
    }

    public function appendTo(BitArray $bitArray, array $text)
    {
        $bitArray->append($this->value, $this->bitCount);

        return $bitArray;
    }

    public function __toString()
    {
        return sprintf('%s [ %0'.$this->bitCount.'b ]', __CLASS__, $this->value);
    }
}
