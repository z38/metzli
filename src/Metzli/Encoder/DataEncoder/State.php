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

class State
{
    private $mode;
    private $token;
    private $shiftByteCount;
    private $bitCount;

    private function __construct(Token $token, $mode, $binaryBytes, $bitCount)
    {
        $this->token = $token;
        $this->mode = $mode;
        $this->shiftByteCount = $binaryBytes;
        $this->bitCount = $bitCount;
    }

    public function getMode()
    {
        return $this->mode;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function getBinaryShiftByteCount()
    {
        return $this->shiftByteCount;
    }

    public function getBitCount()
    {
        return $this->bitCount;
    }

    public static function createInitialState()
    {
        return new self(Token::createEmpty(), DynamicDataEncoder::MODE_UPPER, 0, 0);
    }

    public function latchAndAppend($mode, $value)
    {
        $bitCount = $this->bitCount;
        $token = $this->token;
        if ($mode != $this->mode) {
            $latch = DynamicDataEncoder::getLatch($this->mode, $mode);
            $token = $token->add(($latch & 0xFFFF), ($latch >> 16));
            $bitCount += ($latch >> 16);
        }
        $latchModeBitCount = ($mode == DynamicDataEncoder::MODE_DIGIT ? 4 : 5);
        $token = $token->add($value, $latchModeBitCount);

        return new self($token, $mode, 0, $bitCount + $latchModeBitCount);
    }

    public function shiftAndAppend($mode, $value)
    {
        $token = $this->token;
        $thisModeBitCount = ($this->mode == DynamicDataEncoder::MODE_DIGIT ? 4 : 5);
        $token = $token->add(DynamicDataEncoder::getShift($this->mode, $mode), $thisModeBitCount);
        $token = $token->add($value, 5);

        return new self($token, $this->mode, 0, $this->bitCount + $thisModeBitCount + 5);
    }

    public function addBinaryShiftChar($index)
    {
        $token = $this->token;
        $mode = $this->mode;
        $bitCount = $this->bitCount;
        if ($this->mode == DynamicDataEncoder::MODE_PUNCT || $this->mode == DynamicDataEncoder::MODE_DIGIT) {
            $latch = DynamicDataEncoder::getLatch($mode, DynamicDataEncoder::MODE_UPPER);
            $token = $token->add(($latch & 0xFFFF), ($latch >> 16));
            $bitCount += ($latch >> 16);
            $mode = DynamicDataEncoder::MODE_UPPER;
        }

        if ($this->shiftByteCount == 0 || $this->shiftByteCount == 31) {
            $deltaBitCount = 18;
        } elseif ($this->shiftByteCount == 62) {
            $deltaBitCount = 9;
        } else {
            $deltaBitCount = 8;
        }
        $result = new self($token, $mode, $this->shiftByteCount + 1, $bitCount + $deltaBitCount);
        if ($result->getBinaryShiftByteCount() == (2047 + 31)) {
            $result = $result->endBinaryShift($index + 1);
        }

        return $result;
    }

    public function endBinaryShift($index)
    {
        if ($this->shiftByteCount == 0) {
            return $this;
        }
        $token = $this->token;
        $token = $token->addBinaryShift($index - $this->shiftByteCount, $this->shiftByteCount);

        return new self($token, $this->mode, 0, $this->bitCount);
    }

    public function isBetterThanOrEqualTo(State $other)
    {
        $mySize = $this->bitCount + (DynamicDataEncoder::getLatch($this->getMode(), $other->getMode()) >> 16);
        if ($other->getBinaryShiftByteCount() > 0 && ($this->getBinaryShiftByteCount() == 0 || $this->getBinaryShiftByteCount() > $other->getBinaryShiftByteCount())) {
            $mySize += 10;
        }

        return ($mySize <= $other->getBitCount());
    }

    public function toBitArray(array $text)
    {
        $symbols = array();
        $token = $this->endBinaryShift(count($text))->getToken();
        while ($token !== null) {
            array_unshift($symbols, $token);
            $token = $token->getPrevious();
        }

        $bitArray = new BitArray();
        foreach ($symbols as $symbol) {
            $bitArray = $symbol->appendTo($bitArray, $text);
        }

        return $bitArray;
    }

    public function __toString()
    {
        $tokens = array();
        $token = $this->token;
        while ($token != null) {
            $tokens[] = (string) $token;
            $token = $token->getPrevious();
        }

        return sprintf('%s { mode = %s, bits = %d, shiftBytes = %d, tokens = [ %s ] }', __CLASS__, DynamicDataEncoder::getModeName($this->mode), $this->bitCount, $this->shiftByteCount, implode(', ', array_reverse($tokens)));
    }
}
