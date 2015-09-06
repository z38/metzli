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

class DynamicDataEncoder implements DataEncoderInterface
{
    const MODE_UPPER = 0;
    const MODE_LOWER = 1;
    const MODE_DIGIT = 2;
    const MODE_MIXED = 3;
    const MODE_PUNCT = 4;

    private static $shiftTable = null;
    private static $latchTable = null;
    private static $charMap = null;
    private static $modeNames = array('UPPER', 'LOWER', 'DIGIT', 'MIXED', 'PUNCT');

    public function encode($data)
    {
        $text = str_split($data);
        $states = array(State::createInitialState());
        for ($index = 0; $index < count($text); $index++) {
            $nextChar = (($index + 1 < count($text)) ? $text[$index + 1] : '');
            switch ($text[$index]) {
                case '\r':
                    $pairCode = (($nextChar == '\n') ? 2 : 0);
                    break;
                case '.':
                    $pairCode = (($nextChar == ' ') ? 3 : 0);
                    break;
                case ',':
                    $pairCode = (($nextChar == ' ') ? 4 : 0);
                    break;
                case ':':
                    $pairCode = (($nextChar == ' ') ? 5 : 0);
                    break;
                default:
                    $pairCode = 0;
                    break;
            }
            if ($pairCode > 0) {
                $states = self::updateStateListForPair($states, $index, $pairCode);
                $index++;
            } else {
                $states = self::updateStateListForChar($states, $index, $text);
            }
        }

        $minState = $states[0];
        foreach ($states as $state) {
            if ($state->getBitCount() < $minState->getBitCount()) {
                $minState = $state;
            }
        }

        return $minState->toBitArray($text);
    }

    private static function updateStateListForChar(array $states, $index, $text)
    {
        $result = array();
        foreach ($states as $state) {
            $result = self::updateStateForChar($state, $index, $text, $result);
        }

        return self::simplifyStates($result);
    }

    private static function updateStateForChar(State $state, $index, $text, $result = array())
    {
        $ch = $text[$index];
        $charInCurrentTable = (self::getCharMapping($state->getMode(), $ch) > 0);
        $stateNoBinary = null;
        for ($mode = 0; $mode <= self::MODE_PUNCT; $mode++) {
            $charInMode = self::getCharMapping($mode, $ch);
            if ($charInMode > 0) {
                if ($stateNoBinary === null) {
                    $stateNoBinary = $state->endBinaryShift($index);
                }
                if (!$charInCurrentTable || $mode == $state->getMode() || $mode == self::MODE_DIGIT) {
                    $result[] = $stateNoBinary->latchAndAppend($mode, $charInMode);
                }
                if (!$charInCurrentTable && self::getShift($state->getMode(), $mode) >= 0) {
                    $result[] = $stateNoBinary->shiftAndAppend($mode, $charInMode);
                }
            }
        }
        if ($state->getBinaryShiftByteCount() > 0 || self::getCharMapping($state->getMode(), $ch) == 0) {
            $result[] = $state->addbinaryShiftChar($index);
        }

        return $result;
    }

    private static function updateStateListForPair(array $states, $index, $pairCode)
    {
        $result = array();
        foreach ($states as $state) {
            $result = self::updateStateForPair($state, $index, $pairCode, $result);
        }

        return self::simplifyStates($result);
    }

    private static function updateStateForPair(State $state, $index, $pairCode, $result = array())
    {
        $stateNoBinary = $state->endBinaryShift($index);

        $result[] = $stateNoBinary->latchAndAppend(self::MODE_PUNCT, $pairCode);
        if ($state->getMode() != self::MODE_PUNCT) {
            $result[] = $stateNoBinary->shiftAndAppend(self::MODE_PUNCT, $pairCode);
        }
        if ($pairCode == 3 || $pairCode == 4) {
            $result[] = $stateNoBinary->latchAndAppend(self::MODE_DIGIT, 16 - $pairCode)->latchAndAppend(self::MODE_DIGIT, 1);
        }
        if ($state->getBinaryShiftByteCount() > 0) {
            $result[] = $state->addBinaryShiftChar($index)->addBinaryShiftChar($index + 1);
        }

        return $result;
    }

    private static function simplifyStates(array $states)
    {
        $result = array();
        foreach ($states as $newState) {
            $add = true;
            for ($i = 0; $i < count($result); $i++) {
                if ($result[$i]->isBetterThanOrEqualTo($newState)) {
                    $add = false;
                    break;
                }
                if ($newState->isBetterThanOrEqualTo($result[$i])) {
                    unset($result[$i]);
                    $result = array_values($result);
                    $i--;
                }
            }
            if ($add) {
                $result[] = $newState;
            }
        }

        return $result;
    }

    public static function getModeName($mode)
    {
        return self::$modeNames[$mode];
    }

    public static function getLatch($fromMode, $toMode)
    {
        if (null === self::$latchTable) {
            self::$latchTable = array(
                array(
                    0,
                    (5 << 16) + 28,                           // UPPER -> LOWER
                    (5 << 16) + 30,                           // UPPER -> DIGIT
                    (5 << 16) + 29,                           // UPPER -> MIXED
                    (10 << 16) + (29 << 5) + 30,              // UPPER -> MIXED -> PUNCT
                ), array(
                    (9 << 16) + (30 << 4) + 14,               // LOWER -> DIGIT -> UPPER
                    0,
                    (5 << 16) + 30,                           // LOWER -> DIGIT
                    (5 << 16) + 29,                           // LOWER -> MIXED
                    (10 << 16) + (29 << 5) + 30,              // LOWER -> MIXED -> PUNCT
                ), array(
                    (4 << 16) + 14,                           // DIGIT -> UPPER
                    (9 << 16) + (14 << 5) + 28,               // DIGIT -> UPPER -> LOWER
                    0,
                    (9 << 16) + (14 << 5) + 29,               // DIGIT -> UPPER -> MIXED
                    (14 << 16) + (14 << 10) + (29 << 5) + 30, // DIGIT -> UPPER -> MIXED -> PUNCT
                ), array(
                    (5 << 16) + 29,                           // MIXED -> UPPER
                    (5 << 16) + 28,                           // MIXED -> LOWER
                    (10 << 16) + (29 << 5) + 30,              // MIXED -> UPPER -> DIGIT
                    0,
                    (5 << 16) + 30,                           // MIXED -> PUNCT
                ), array(
                    (5 << 16) + 31,                           // PUNCT -> UPPER
                    (10 << 16) + (31 << 5) + 28,              // PUNCT -> UPPER -> LOWER
                    (10 << 16) + (31 << 5) + 30,              // PUNCT -> UPPER -> DIGIT
                    (10 << 16) + (31 << 5) + 29,              // PUNCT -> UPPER -> MIXED
                    0,
                ),
            );
        }

        return self::$latchTable[$fromMode][$toMode];
    }

    public static function getShift($fromMode, $toMode)
    {
        if (null === self::$shiftTable) {
            self::$shiftTable = array();
            for ($i = 0; $i < 6; $i++) {
                self::$shiftTable[] = array_fill(0, 6, -1);
            }
            self::$shiftTable[self::MODE_UPPER][self::MODE_PUNCT] = 0;
            self::$shiftTable[self::MODE_LOWER][self::MODE_PUNCT] = 0;
            self::$shiftTable[self::MODE_LOWER][self::MODE_UPPER] = 28;

            self::$shiftTable[self::MODE_MIXED][self::MODE_PUNCT] = 0;

            self::$shiftTable[self::MODE_DIGIT][self::MODE_PUNCT] = 0;
            self::$shiftTable[self::MODE_DIGIT][self::MODE_UPPER] = 15;
        }

        return self::$shiftTable[$fromMode][$toMode];
    }

    public static function getCharMapping($mode, $char)
    {
        if (null === self::$charMap) {
            self::$charMap = array();
            for ($i = 0; $i < 5; $i++) {
                self::$charMap[] = array_fill(0, 256, 0);
            }

            self::$charMap[self::MODE_UPPER][ord(' ')] = 1;
            for ($c = ord('A'); $c <= ord('Z'); $c++) {
                self::$charMap[self::MODE_UPPER][$c] = ($c - ord('A') + 2);
            }

            self::$charMap[self::MODE_LOWER][ord(' ')] = 1;
            for ($c = ord('a'); $c <= ord('z'); $c++) {
                self::$charMap[self::MODE_LOWER][$c] = ($c - ord('a') + 2);
            }

            self::$charMap[self::MODE_DIGIT][ord(' ')] = 1;
            for ($c = ord('0'); $c <= ord('9'); $c++) {
                self::$charMap[self::MODE_DIGIT][$c] = ($c - ord('0') + 2);
            }
            self::$charMap[self::MODE_DIGIT][ord(',')] = 12;
            self::$charMap[self::MODE_DIGIT][ord('.')] = 13;

            $mixedTable = array(
                '\0', ' ', '\1', '\2', '\3', '\4', '\5', '\6', '\7', '\b', '\t', '\n',
                '\13', '\f', '\r', '\33', '\34', '\35', '\36', '\37', '@', '\\', '^',
                '_', '`', '|', '~', '\177',
            );
            for ($i = 0; $i < count($mixedTable); $i++) {
                self::$charMap[self::MODE_MIXED][ord($mixedTable[$i])] = $i;
            }

            $punctTable = array(
                '\0', '\r', '\0', '\0', '\0', '\0', '!', '\'', '#', '$', '%', '&', '\'',
                '(', ')', '*', '+', ',', '-', '.', '/', ':', ';', '<', '=', '>', '?',
                '[', ']', '{', '}',
            );
            for ($i = 0; $i < count($punctTable); $i++) {
                if (ord($punctTable[$i]) > 0) {
                    self::$charMap[self::MODE_PUNCT][ord($punctTable[$i])] = $i;
                }
            }
        }

        return self::$charMap[$mode][ord($char)];
    }
}
