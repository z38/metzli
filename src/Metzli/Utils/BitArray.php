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

class BitArray
{
    private $data;

    public function __construct($length = 0)
    {
        $this->data = array();
        $this->resize($length);
    }

    public function resize($length)
    {
        if ($length > count($this->data)) {
            $this->data = array_pad($this->data, $length, 0);
        } elseif ($length < count($this->data)) {
            $this->data = array_splice($this->data, 0, $length);
        }
    }

    public function getLength()
    {
        return count($this->data);
    }

    public function get($index)
    {
        $this->checkIndex($index);

        return $this->data[$index];
    }

    public function tryGet($index)
    {
        if ($index < 0 || $index >= count($this->data)) {
            return 0;
        } else {
            return $this->data[$index];
        }
    }

    public function set($index, $bit = 1)
    {
        $this->checkIndex($index);
        $this->data[$index] = ($bit & 1);
    }

    public function flip($index)
    {
        $this->checkIndex($index);
        $this->data[$index] = (1 - $this->data[$index]);
    }

    public function clear()
    {
        for ($i = 0; $i < count($this->data); $i++) {
            $this->data[$i] = 0;
        }
    }

    public function append($data, $bits = 1)
    {
        for ($i = $bits - 1; $i >= 0; $i--) {
            $this->data[] = ($data >> $i) & 1;
        }
    }

    public function appendBytes($bytes)
    {
        for ($i = 0; $i < strlen($bytes); $i++) {
            $this->append(ord($bytes[$i]), 8);
        }
    }

    public function asArray()
    {
        return $this->data;
    }

    public function __toString()
    {
        $bytes = str_split(implode($this->data), 8);

        return sprintf("%s [ %s ]", __CLASS__, implode(' ', $bytes));
    }

    private function checkIndex($index)
    {
        if ($index < 0 || $index >= count($this->data)) {
            throw new \OutOfRangeException();
        }
    }
}
