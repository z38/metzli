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

namespace Metzli\Tests\Encoder\DataEncoder;

use Metzli\Encoder\DataEncoder\DynamicDataEncoder;
use Metzli\Tests\TestCase;

class DynamicDataEncoderTest extends TestCase
{
    public function testEncode()
    {
        $this->encodeTest("A. b.", "...X. ..... ...XX XXX.. ...XX XXXX. XX.X");
        $this->encodeTest("Lorem ipsum.", ".XX.X XXX.. X.... X..XX ..XX. .XXX. ....X .X.X. X...X X.X.. X.XX. .XXX. XXXX. XX.X");
        $this->encodeTest("Lo. Test 123.", ".XX.X XXX.. X.... ..... ...XX XXX.. X.X.X ..XX. X.X.. X.X.X  XXXX. ...X ..XX .X.. .X.X XX.X");
        $this->encodeTest("Lo...x", ".XX.X XXX.. X.... XXXX. XX.X XX.X XX.X XXX. XXX.. XX..X");
        $this->encodeTest(". x://abc/.", "..... ...XX XXX.. XX..X ..... X.X.X ..... X.X.. ..... X.X.. ...X. ...XX ..X.. ..... X.X.. XXXX. XX.X");
        $this->encodeTest("ABCdEFG", "...X. ...XX ..X.. XXXXX ....X .XX..X.. ..XX. ..XXX .X...");
        $this->encodeTest("N\0N", ".XXXX XXXXX ....X ........ .XXXX");
        $this->encodeTest("N\0n", ".XXXX XXXXX ...X. ........ .XX.XXX.");
        $this->encodeTest("N\0\xc2\x80 A", ".XXXX XXXXX ...X. ........ X....... ....X ...X.");
        $this->encodeTest("\0a\xc3\xbf\xc2\x80 A", "XXXXX ..X.. ........ .XX....X XXXXXXXX X....... ....X ...X.");
        $this->encodeTest("1234\0", "XXXX. ..XX .X.. .X.X .XX. XXX. XXXXX ....X ........");

        $this->encodeBitCountTest("09  UAG    ^160MEUCIQC0sYS/HpKxnBELR1uB85R20OoqqwFGa0q2uEiYgh6utAIgLl1aBVM4EOTQtMQQYH9M2Z3Dp4qnA/fwWuQ+M8L3V8U=", 823);

        $testString = '';
        for ($i = 0; $i <= 3000; $i++) {
            $testString .= chr(128 + ($i % 30));
        }
        $testIndices = array(1, 2, 3, 10, 29, 30, 31, 32, 33, 60, 61, 62, 63, 64, 2076, 2077, 2078, 2079, 2080, 2100);
        foreach ($testIndices as $i) {
            $expectedLength = (8 * $i);
            if ($i <= 31) {
                $expectedLength += 10;
            } elseif ($i <= 62) {
                $expectedLength += 20;
            } elseif ($i <= 2078) {
                $expectedLength += 21;
            } else {
                $expectedLength += 31;
            }

            $this->encodeBitCountTest(substr($testString, 0, $i), $expectedLength);
            if ($i != 1 && $i != 32 && $i != 2079) {
                $this->encodeBitCountTest('a'.substr($testString, 0, $i - 1), $expectedLength);
                $this->encodeBitCountTest(substr($testString, 0, $i - 1).'a', $expectedLength);
            }
            $this->encodeBitCountTest('a'.substr($testString, 0, $i).'b', $expectedLength + 15);
        }
    }

    private function encodeTest($data, $expectedBits)
    {
        $encoder = new DynamicDataEncoder();
        $bits = $encoder->encode(self::convertToLatin1($data));
        $this->assertEquals(self::toBitArray($expectedBits), $bits);
    }

    private function encodeBitCountTest($data, $expectedBitCount)
    {
        $encoder = new DynamicDataEncoder();
        $bits = $encoder->encode($data);
        $this->assertEquals($expectedBitCount, $bits->getLength());
    }

    private static function convertToLatin1($data)
    {
        return iconv('UTF-8', 'ISO-8859-1//IGNORE', $data);
    }
}
