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

namespace Metzli\Utils;

class BitMatrix
{
    private $data;
    private $width;
    private $height;

    public function __construct($width, $height = null)
    {
        if ($height === null) {
            $height = $width;
        }
        if ($width <= 0 || $height <= 0) {
            throw new \InvalidArgumentException('Cannot create empty matrix');
        }
        $this->width = $width;
        $this->height = $height;
        $this->data = new BitArray($this->width * $this->height);
    }

    public function set($x, $y, $bit = 1)
    {
        $index = $this->getIndex($x, $y);
        $this->data->set($index, $bit);
    }

    public function get($x, $y)
    {
        $index = $this->getIndex($x, $y);

        return $this->data->get($index);
    }

    public function flip($x, $y)
    {
        $index = $this->getIndex($x, $y);
        $this->data->flip($index);
    }

    public function clear()
    {
        $this->data->clear();
    }

    public function getWidth()
    {
        return $this->width;
    }

    public function getHeight()
    {
        return $this->height;
    }

    private function getIndex($x, $y)
    {
        if ($x < 0 || $x >= $this->width) {
            throw new\OutOfRangeException();
        }
        if ($y < 0 || $y >= $this->height) {
            throw new\OutOfRangeException();
        }

        return ($x + $y * $this->width);
    }

    public function __toString()
    {
        $result = __CLASS__." [\n";
        for ($y = 0; $y < $this->height; $y++) {
            for ($x = 0; $x < $this->width; $x++) {
                $result .= ($this->get($x, $y) ? 'X' : '.');
            }
            $result .= "\n";
        }
        $result .= "]";

        return $result;
    }
}
