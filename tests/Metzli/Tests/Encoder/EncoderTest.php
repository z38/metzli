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

namespace Metzli\Tests\Encoder;

use Metzli\Encoder\Encoder;
use Metzli\Tests\TestCase;

class EncoderTest extends TestCase
{
    public function testGenerateModeMessage()
    {
        $this->modeMessageTest(true, 2, 29, '.X .XXX.. ...X XX.. ..X .XX. .XX.X');
        $this->modeMessageTest(true, 4, 64, 'XX XXXXXX .X.. ...X ..XX .X.. XX..');
        $this->modeMessageTest(false, 21, 660,  'X.X.. .X.X..X..XX .XXX ..X.. .XXX. .X... ..XXX');
        $this->modeMessageTest(false, 32, 4096, 'XXXXX XXXXXXXXXXX X.X. ..... XXX.X ..X.. X.XXX');
    }

    private function modeMessageTest($compact, $layers, $words, $expected)
    {
        $result = Encoder::generateModeMessage($compact, $layers, $words);
        $this->assertEquals(self::toBitArray($expected), $result);
    }

    public function testStuffBits()
    {
        $this->stuffBitsTest(5, '.X.X. X.X.X .X.X.', '.X.X. X.X.X .X.X.');
        $this->stuffBitsTest(5, '.X.X. ..... .X.X', '.X.X. ....X ..X.X');
        $this->stuffBitsTest(3, 'XX. ... ... ..X XXX .X. ..', 'XX. ..X ..X ..X ..X .XX XX. .X. ..X');
        $this->stuffBitsTest(6, '.X.X.. ...... ..X.XX', '.X.X.. .....X. ..X.XX XXXX.');
        $this->stuffBitsTest(6, '.X.X.. ...... ...... ..X.X.', '.X.X.. .....X .....X ....X. X.XXXX');
        $this->stuffBitsTest(6, '.X.X.. XXXXXX ...... ..X.XX', '.X.X.. XXXXX. X..... ...X.X XXXXX.');
        $this->stuffBitsTest(6, '...... ..XXXX X..XX. .X.... .X.X.X .....X .X.... ...X.X .....X ....XX ..X... ....X. X..XXX X.XX.X', '.....X ...XXX XX..XX ..X... ..X.X. X..... X.X... ....X. X..... X....X X..X.. .....X X.X..X XXX.XX .XXXXX');
    }

    private function stuffBitsTest($wordSize, $bits, $expected)
    {
        $in = self::toBitArray($bits);
        $stuffed = Encoder::stuffBits($in, $wordSize);
        $this->assertEquals(self::toBitArray($expected), $stuffed);
    }
}
