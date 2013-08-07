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

class DivisionResult
{
    private $quotient;
    private $remainder;

    public function __construct($quotient, $remainder)
    {
        $this->checkType($quotient);
        $this->checkType($remainder);
        $this->quotient = $quotient;
        $this->remainder = $remainder;
    }

    private function checkType($number)
    {
        if (!is_int($number) && !is_float($number) && !($number instanceof GenericGFPoly)) {
            throw new \InvalidArgumentException('Non-numbers are not allowed');
        }
    }

    public function getQuotient()
    {
        return $this->quotient;
    }

    public function getRemainder()
    {
        return $this->remainder;
    }
}
