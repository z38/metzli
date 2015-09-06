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

namespace Metzli\Encoder;

use Metzli\Encoder\DataEncoder\DataEncoderInterface;
use Metzli\Encoder\DataEncoder\DynamicDataEncoder;
use Metzli\Encoder\ReedSolomon\GenericGF;
use Metzli\Encoder\ReedSolomon\ReedSolomonEncoder;
use Metzli\Utils\BitArray;
use Metzli\Utils\BitMatrix;

class Encoder
{
    const DEFAULT_EC_PERCENT = 33;
    const LAYERS_COMPACT = 5;
    const LAYERS_FULL = 33;

    private static $wordSize = array(
         4,  6,  6,  8,  8,  8,  8,  8,  8, 10, 10,
        10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10,
        10, 12, 12, 12, 12, 12, 12, 12, 12, 12, 12,
    );

    private function __construct()
    {
    }

    public static function encode($content, $eccPercent = self::DEFAULT_EC_PERCENT, $dataEncoder = null)
    {
        if (strlen($content) == 0) {
            throw new \InvalidArgumentException('No content provided');
        }

        if (null === $dataEncoder) {
            $dataEncoder = new DynamicDataEncoder();
        } elseif (!($dataEncoder instanceof DataEncoderInterface)) {
            throw new \InvalidArgumentException('dataEncoder has to implement DataEncoderInterface');
        }
        $bits = $dataEncoder->encode($content);

        $eccBits = intval($bits->getLength() * $eccPercent / 100 + 11);
        $totalSizeBits = $bits->getLength() + $eccBits;

        $layers = 0;
        $wordSize = 0;
        $totalSymbolBits = 0;
        $stuffedBits = null;
        for ($layers = 1; $layers < self::LAYERS_COMPACT; $layers++) {
            if (self::getBitsPerLayer($layers, false) >= $totalSizeBits) {
                if ($wordSize != self::$wordSize[$layers]) {
                    $wordSize = self::$wordSize[$layers];
                    $stuffedBits = self::stuffBits($bits, $wordSize);
                }

                $totalSymbolBits = self::getBitsPerLayer($layers, false);
                if ($stuffedBits->getLength() + $eccBits <= $totalSymbolBits) {
                    break;
                }
            }
        }
        $compact = true;
        if ($layers == self::LAYERS_COMPACT) {
            $compact = false;
            for ($layers = 1; $layers < self::LAYERS_FULL; $layers++) {
                if (self::getBitsPerLayer($layers, true) >= $totalSizeBits) {
                    if ($wordSize != self::$wordSize[$layers]) {
                        $wordSize = self::$wordSize[$layers];
                        $stuffedBits = self::stuffBits($bits, $wordSize);
                    }
                    $totalSymbolBits = self::getBitsPerLayer($layers, true);
                    if ($stuffedBits->getLength() + $eccBits <= $totalSymbolBits) {
                        break;
                    }
                }
            }
        }
        if ($layers == self::LAYERS_FULL) {
            throw new \InvalidArgumentException('Data too large');
        }

        $messageSizeInWords = intval(($stuffedBits->getLength() + $wordSize - 1) / $wordSize);
        for ($i = $messageSizeInWords * $wordSize - $stuffedBits->getLength(); $i > 0; $i--) {
            $stuffedBits->append(1);
        }

        // generate check words
        $rs = new ReedSolomonEncoder(self::getGF($wordSize));
        $totalSizeInFullWords = intval($totalSymbolBits / $wordSize);
        $messageWords = self::bitsToWords($stuffedBits, $wordSize, $totalSizeInFullWords);
        $messageWords = $rs->encodePadded($messageWords, $totalSizeInFullWords - $messageSizeInWords);

        // convert to bit array and pad in the beginning
        $startPad = $totalSymbolBits % $wordSize;
        $messageBits = new BitArray();
        $messageBits->append(0, $startPad);
        foreach ($messageWords as $messageWord) {
            $messageBits->append($messageWord, $wordSize);
        }

        // generate mode message
        $modeMessage = self::generateModeMessage($compact, $layers, $messageSizeInWords);

        // allocate symbol
        if ($compact) {
            $matrixSize = $baseMatrixSize = 11 + $layers * 4;
            $alignmentMap = array();
            for ($i = 0; $i < $matrixSize; $i++) {
                $alignmentMap[] = $i;
            }
        } else {
            $baseMatrixSize = 14 + $layers * 4;
            $matrixSize = $baseMatrixSize + 1 + 2 * intval((intval($baseMatrixSize / 2) - 1) / 15);
            $alignmentMap = array_fill(0, $baseMatrixSize, 0);
            $origCenter = intval($baseMatrixSize / 2);
            $center = intval($matrixSize / 2);
            for ($i = 0; $i < $origCenter; $i++) {
                $newOffset = $i + intval($i / 15);
                $alignmentMap[$origCenter - $i - 1] = $center - $newOffset - 1;
                $alignmentMap[$origCenter + $i] = $center + $newOffset + 1;
            }
        }
        $matrix = new BitMatrix($matrixSize);

        // draw mode and data bits
        for ($i = 0, $rowOffset = 0; $i < $layers; $i++) {
            if ($compact) {
                $rowSize = ($layers - $i) * 4 + 9;
            } else {
                $rowSize = ($layers - $i) * 4 + 12;
            }
            for ($j = 0; $j < $rowSize; $j++) {
                $columnOffset = $j * 2;
                for ($k = 0; $k < 2; $k++) {
                    if ($messageBits->get($rowOffset + $columnOffset + $k)) {
                        $matrix->set($alignmentMap[$i * 2 + $k], $alignmentMap[$i * 2 + $j]);
                    }
                    if ($messageBits->get($rowOffset + $rowSize * 2 + $columnOffset + $k)) {
                        $matrix->set($alignmentMap[$i * 2 + $j], $alignmentMap[$baseMatrixSize - 1 - $i * 2 - $k]);
                    }
                    if ($messageBits->get($rowOffset + $rowSize * 4 + $columnOffset + $k)) {
                        $matrix->set($alignmentMap[$baseMatrixSize - 1 - $i * 2 - $k], $alignmentMap[$baseMatrixSize - 1 - $i * 2 - $j]);
                    }
                    if ($messageBits->get($rowOffset + $rowSize * 6 + $columnOffset + $k)) {
                        $matrix->set($alignmentMap[$baseMatrixSize - 1 - $i * 2 - $j], $alignmentMap[$i * 2 + $k]);
                    }
                }
            }
            $rowOffset += $rowSize * 8;
        }

        $matrix = self::drawModeMessage($matrix, $compact, $matrixSize, $modeMessage);

        // draw alignment marks
        if ($compact) {
            $matrix = self::drawBullsEye($matrix, intval($matrixSize / 2), 5);
        } else {
            $matrix = self::drawBullsEye($matrix, intval($matrixSize / 2), 7);
            for ($i = 0, $j = 0; $i < intval($baseMatrixSize / 2) - 1; $i += 15, $j += 16) {
                for ($k = intval($matrixSize / 2) & 1; $k < $matrixSize; $k += 2) {
                    $matrix->set(intval($matrixSize / 2) - $j, $k);
                    $matrix->set(intval($matrixSize / 2) + $j, $k);
                    $matrix->set($k, intval($matrixSize / 2) - $j);
                    $matrix->set($k, intval($matrixSize / 2) + $j);
                }
            }
        }
        $code = new AztecCode();
        $code->setCompact($compact);
        $code->setSize($matrixSize);
        $code->setLayers($layers);
        $code->setCodeWords($messageSizeInWords);
        $code->setMatrix($matrix);

        return $code;
    }

    private static function getBitsPerLayer($layer, $full = true)
    {
        if ($full) {
            return (112 + 16 * $layer) * $layer;
        } else {
            return (88 + 16 * $layer) * $layer;
        }
    }

    private static function bitsToWords(BitArray $stuffedBits, $wordSize, $totalWords)
    {
        $message = array_fill(0, $totalWords, 0);
        $n = intval($stuffedBits->getLength() / $wordSize);
        for ($i = 0; $i < $n; $i++) {
            $value = 0;
            for ($j = 0; $j < $wordSize; $j++) {
                $value |= $stuffedBits->get($i * $wordSize + $j) ? (1 << $wordSize - $j - 1) : 0;
            }
            $message[$i] = $value;
        }

        return $message;
    }

    private static function getGF($wordSize)
    {
        switch ($wordSize) {
            case 4:
                return GenericGF::getInstance(GenericGF::AZTEC_PARAM);
            case 6:
                return GenericGF::getInstance(GenericGF::AZTEC_DATA_6);
            case 8:
                return GenericGF::getInstance(GenericGF::AZTEC_DATA_8);
            case 10:
                return GenericGF::getInstance(GenericGF::AZTEC_DATA_10);
            case 12:
                return GenericGF::getInstance(GenericGF::AZTEC_DATA_12);
            default:
                return null;
        }
    }

    public static function stuffBits(BitArray $bits, $wordSize)
    {
        $out = new BitArray();

        $n = $bits->getLength();
        $mask = (1 << $wordSize) - 2;
        for ($i = 0; $i < $n; $i += $wordSize) {
            $word = 0;
            for ($j = 0; $j < $wordSize; $j++) {
                if ($i + $j >= $n || $bits->get($i + $j)) {
                    $word |= 1 << ($wordSize - 1 - $j);
                }
            }
            if (($word & $mask) == $mask) {
                $out->append($word & $mask, $wordSize);
                $i--;
            } elseif (($word & $mask) == 0) {
                $out->append($word | 1, $wordSize);
                $i--;
            } else {
                $out->append($word, $wordSize);
            }
        }

        $n = $out->getLength();
        $remainder = $n % $wordSize;
        if ($remainder != 0) {
            $j = 1;
            for ($i = 0; $i < $remainder; $i++) {
                if (!$out->get($n - 1 - $i)) {
                    $j = 0;
                }
            }
            for ($i = $remainder; $i < $wordSize - 1; $i++) {
                $out->append(1);
            }
            $out->append((($j == 0) ? 1 : 0));
        }

        return $out;
    }

    public static function generateModeMessage($compact, $layers, $messageSizeInWords)
    {
        $modeMessage = new BitArray();
        if ($compact) {
            $modeMessage->append($layers - 1, 2);
            $modeMessage->append($messageSizeInWords - 1, 6);
            $modeMessage = self::generateCheckWords($modeMessage, 28, 4);
        } else {
            $modeMessage->append($layers - 1, 5);
            $modeMessage->append($messageSizeInWords - 1, 11);
            $modeMessage = self::generateCheckWords($modeMessage, 40, 4);
        }

        return $modeMessage;
    }

    public static function generateCheckWords(BitArray $stuffedBits, $totalSymbolBits, $wordSize)
    {
        $messageSizeInWords = intval(($stuffedBits->getLength() + $wordSize - 1) / $wordSize);
        for ($i = $messageSizeInWords * $wordSize - $stuffedBits->getLength(); $i > 0; $i--) {
            $stuffedBits->append(1);
        }
        $totalSizeInFullWords = intval($totalSymbolBits / $wordSize);
        $messageWords = self::bitsToWords($stuffedBits, $wordSize, $totalSizeInFullWords);

        $rs = new ReedSolomonEncoder(self::getGF($wordSize));
        $messageWords = $rs->encodePadded($messageWords, $totalSizeInFullWords - $messageSizeInWords);

        $startPad = $totalSymbolBits % $wordSize;
        $messageBits = new BitArray();
        $messageBits->append(0, $startPad);

        foreach ($messageWords as $messageWord) {
            $messageBits->append($messageWord, $wordSize);
        }

        return $messageBits;
    }

    private static function drawBullsEye(BitMatrix $matrix, $center, $size)
    {
        for ($i = 0; $i < $size; $i += 2) {
            for ($j = $center - $i; $j <= $center + $i; $j++) {
                $matrix->set($j, $center - $i);
                $matrix->set($j, $center + $i);
                $matrix->set($center - $i, $j);
                $matrix->set($center + $i, $j);
            }
        }
        $matrix->set($center - $size, $center - $size);
        $matrix->set($center - $size + 1, $center - $size);
        $matrix->set($center - $size, $center - $size + 1);
        $matrix->set($center + $size, $center - $size);
        $matrix->set($center + $size, $center - $size + 1);
        $matrix->set($center + $size, $center + $size - 1);

        return $matrix;
    }

    private static function drawModeMessage(BitMatrix $matrix, $compact, $matrixSize, BitArray $modeMessage)
    {
        $center = intval($matrixSize / 2);
        if ($compact) {
            for ($i = 0; $i < 7; $i++) {
                if ($modeMessage->get($i)) {
                    $matrix->set($center - 3 + $i, $center - 5);
                }
                if ($modeMessage->get($i + 7)) {
                    $matrix->set($center + 5, $center - 3 + $i);
                }
                if ($modeMessage->get(20 - $i)) {
                    $matrix->set($center - 3 + $i, $center + 5);
                }
                if ($modeMessage->get(27 - $i)) {
                    $matrix->set($center - 5, $center - 3 + $i);
                }
            }
        } else {
            for ($i = 0; $i < 10; $i++) {
                if ($modeMessage->get($i)) {
                    $matrix->set($center - 5 + $i + intval($i / 5), $center - 7);
                }
                if ($modeMessage->get($i + 10)) {
                    $matrix->set($center + 7, $center - 5 + $i + intval($i / 5));
                }
                if ($modeMessage->get(29 - $i)) {
                    $matrix->set($center - 5 + $i + intval($i / 5), $center + 7);
                }
                if ($modeMessage->get(39 - $i)) {
                    $matrix->set($center - 7, $center - 5 + $i + intval($i / 5));
                }
            }
        }

        return $matrix;
    }
}
