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

namespace Metzli\Encoder\ReedSolomon;

class GenericGF
{
    const AZTEC_DATA_12 = 1; // x^12 + x^6 + x^5 + x^3 + 1
    const AZTEC_DATA_10 = 2; // x^10 + x^3 + 1
    const AZTEC_DATA_8 = 3; // x^8 + x^5 + x^3 + x^2 + 1
    const AZTEC_DATA_6 = 4; // x^6 + x + 1
    const AZTEC_PARAM = 5; // x^4 + x + 1

    private $expTable;
    private $logTable;
    private $primitive;
    private $size;
    private $generatorBase;

    public function __construct($primitive, $size, $generatorBase)
    {
        $this->primitive = $primitive;
        $this->size = $size;
        $this->generatorBase = $generatorBase;

        $this->initialize();
    }

    public static function getInstance($type)
    {
        switch ($type) {
            case self::AZTEC_DATA_12:
                return new self(0x1069, 4096, 1);
            case self::AZTEC_DATA_10:
                return new self(0x409, 1024, 1);
            case self::AZTEC_DATA_8:
                return new self(0x012D, 256, 1);
            case self::AZTEC_DATA_6:
                return new self(0x43, 64, 1);
            case self::AZTEC_PARAM:
                return new self(0x13, 16, 1);
            default:
                throw new \InvalidArgumentException('No such type defined');
        }
    }

    private function initialize()
    {
        $this->expTable = array_fill(0, $this->size, 0);
        $this->logTable = array_fill(0, $this->size, 0);
        $x = 1;
        for ($i = 0; $i < $this->size; $i++) {
            $this->expTable[$i] = $x;
            $x <<= 1;
            if ($x >= $this->size) {
                $x ^= $this->primitive;
                $x &= ($this->size - 1);
            }
        }
        for ($i = 0; $i < $this->size; $i++) {
            $this->logTable[$this->expTable[$i]] = $i;
        }
    }

    public function exp($a)
    {
        return $this->expTable[$a];
    }

    public function log($a)
    {
        if ($a == 0) {
            throw new \InvalidArgumentException();
        }

        return $this->logTable[$a];
    }

    public function multiply($a, $b)
    {
        if ($a == 0 || $b == 0) {
            return 0;
        }

        return $this->expTable[($this->logTable[$a] + $this->logTable[$b]) % ($this->size - 1)];
    }

    public function inverse($a)
    {
        if ($a == 0) {
            return new \InvalidArgumentException();
        }

        return $this->expTable[($this->size - $this->logTable[$a] - 1)];
    }

    public function buildMonomial($degree, $coefficient)
    {
        if ($degree < 0) {
            throw new \InvalidArgumentException();
        }
        if ($coefficient == 0) {
            return $this->getZero();
        }

        $coefficients = array_fill(0, ($degree + 1), 0);
        $coefficients[0] = $coefficient;

        return new GenericGFPoly($this, $coefficients);
    }

    public function getSize()
    {
        return $this->size;
    }

    public function getGeneratorBase()
    {
        return $this->generatorBase;
    }

    public function getZero()
    {
        return new GenericGFPoly($this, array(0));
    }

    public static function addOrSubtract($a, $b)
    {
        if (!is_int($a) || !is_int($b)) {
            throw new \InvalidArgumentException('Can not add or substract non-integers');
        }

        return $a ^ $b;
    }
}
