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

class GenericGFPoly
{
    private $field;
    private $coefficients;

    public function __construct(GenericGF $field, array $coefficients)
    {
        if (count($coefficients) == 0) {
            throw new \InvalidArgumentException();
        }

        $this->field = $field;

        while (!empty($coefficients) && $coefficients[0] == 0) {
            array_shift($coefficients);
        }
        if (!empty($coefficients)) {
            $this->coefficients = $coefficients;
        } else {
            $this->coefficients = array(0);
        }
    }

    public function getField()
    {
        return $this->field;
    }

    public function getCoefficients()
    {
        return $this->coefficients;
    }

    public function getDegree()
    {
        return count($this->coefficients) - 1;
    }

    public function isZero()
    {
        return ($this->coefficients[0] == 0);
    }

    public function getCoefficient($degree)
    {
        return $this->coefficients[count($this->coefficients) - 1 - $degree];
    }

    public function addOrSubtract(GenericGFPoly $other)
    {
        if ($other->getField() != $this->field) {
            throw new \InvalidArgumentException('GenericGFPolys do not have same GenericGF field');
        }
        if ($this->isZero()) {
            return $other;
        }
        if ($other->isZero()) {
            return $this;
        }

        $smallerCoefficients = $this->getCoefficients();
        $largerCoefficients = $other->getCoefficients();
        if (count($smallerCoefficients) > count($largerCoefficients)) {
            list($smallerCoefficients, $largerCoefficients) = array($largerCoefficients, $smallerCoefficients);
        }

        $lengthDiff = count($largerCoefficients) - count($smallerCoefficients);
        $sumDiff = array_slice($largerCoefficients, 0, $lengthDiff);

        for ($i = $lengthDiff; $i < count($largerCoefficients); $i++) {
            $sumDiff[$i] = GenericGF::addOrSubtract($smallerCoefficients[$i - $lengthDiff], $largerCoefficients[$i]);
        }

        return new GenericGFPoly($this->field, $sumDiff);
    }

    public function multiply(GenericGFPoly $other)
    {
        if ($other->getField() != $this->field) {
            throw new \InvalidArgumentException('GenericGFPolys do not have same GenericGF field');
        }
        if ($this->isZero() || $other->isZero()) {
            return $this->field->getZero();
        }

        $aCoefficients = $this->getCoefficients();
        $aLength = count($aCoefficients);
        $bCoefficients = $other->getCoefficients();
        $bLength = count($bCoefficients);
        $product = array_fill(0, ($aLength + $bLength - 1), 0);

        for ($i = 0; $i < $aLength; $i++) {
            $aCoeff = $aCoefficients[$i];
            for ($j = 0; $j < $bLength; $j++) {
                $product[$i + $j] = GenericGF::addOrSubtract($product[$i + $j], $this->field->multiply($aCoeff, $bCoefficients[$j]));
            }
        }

        return new GenericGFPoly($this->field, $product);
    }

    public function multiplyByMonomial($degree, $coefficient)
    {
        if ($degree < 0) {
            throw new \InvalidArgumentException();
        }
        if ($coefficient == 0) {
            return $this->field->getZero();
        }

        $product = array_fill(0, (count($this->coefficients) + $degree), 0);
        for ($i = 0; $i < count($this->coefficients); $i++) {
            $product[$i] = $this->field->multiply($this->coefficients[$i], $coefficient);
        }

        return new GenericGFPoly($this->field, $product);
    }

    public function divide(GenericGFPoly $other)
    {
        if ($other->getField() != $this->field) {
            throw new \InvalidArgumentException('GenericGFPolys do not have same GenericGF field');
        }
        if ($other->isZero()) {
            throw new \InvalidArgumentException('Divide by 0');
        }

        $quotient = $this->field->getZero();
        $remainder = $this;

        $denominatorLeadingTerm = $other->getCoefficient($other->getDegree());
        $inverseDenominatorLeadingTerm = $this->field->inverse($denominatorLeadingTerm);
        while ($remainder->getDegree() >= $other->getDegree() && !$remainder->isZero()) {
            $degreeDifference = $remainder->getDegree() - $other->getDegree();
            $scale = $this->field->multiply($remainder->getCoefficient($remainder->getDegree()), $inverseDenominatorLeadingTerm);
            $term = $other->multiplyByMonomial($degreeDifference, $scale);
            $iterationQuotient = $this->field->buildMonomial($degreeDifference, $scale);
            $quotient = $quotient->addOrSubtract($iterationQuotient);
            $remainder = $remainder->addOrSubtract($term);
        }

        return new DivisionResult($quotient, $remainder);
    }

}
