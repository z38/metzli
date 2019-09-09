<?php

/*
 * Copyright 2019 Metzli authors
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

namespace Metzli\Renderer;

use Metzli\Encoder\AztecCode;

class SvgRenderer implements RendererInterface
{
    private $factor;
    private $fgColor;
    private $bgColor;

    public function __construct($factor = 4, $fgColor = '#000000', $bgColor = '#ffffff')
    {
        $this->factor = $factor;
        $this->fgColor = $fgColor;
        $this->bgColor = $bgColor;
    }

    public function render(AztecCode $code)
    {
        $f = $this->factor;
        $matrix = $code->getMatrix();
        $svg = '<?xml version="1.0" standalone="no"?><!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd"> <svg width="' . ($matrix->getWidth() * $f) . '" height="' . ($matrix->getHeight() * $f) . '" version="1.1" xmlns="http://www.w3.org/2000/svg"><g id="barcode" fill="#' . ltrim($this->fgColor, '#') . '"><rect x="0" y="0" width="45" height="45" fill="#' . ltrim($this->bgColor, '#') . '" />';

        for ($x = 0; $x < $matrix->getWidth(); $x++) {
            for ($y = 0; $y < $matrix->getHeight(); $y++) {
                if ($matrix->get($x, $y)) {
                    $svg .= '<rect x="' . ($x * $f) . '" y="' . ($y * $f) . '" width="' . $f . '" height="' . $f . '" />';
                }
            }
        }

        $svg .= '</g></svg>';

        return $svg;
    }
}
