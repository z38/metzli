<?php

/*
 * Copyright 2018 Metzli authors
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

use FPDF;
use Metzli\Encoder\AztecCode;

class FpdfRenderer implements RendererInterface
{
    private $pdf;
    private $x;
    private $y;
    private $size;
    private $fgColor;
    private $bgColor;

    public function __construct(FPDF $pdf, $x, $y, $size, $fgColor = array(0, 0, 0), $bgColor = null)
    {
        $this->pdf = $pdf;
        $this->x = $x;
        $this->y = $y;
        $this->size = $size;
        $this->fgColor = $fgColor;
        $this->bgColor = $bgColor;
    }

    public function render(AztecCode $code, $path = '', $filename='')
    {
        $matrix = $code->getMatrix();

        if ($this->bgColor != null) {
            $this->pdf->SetFillColor($this->bgColor[0], $this->bgColor[1], $this->bgColor[2]);
            $this->pdf->Rect($this->x, $this->y, $this->size, $this->size, 'F');
        }

        $cellWidth = $this->size / $matrix->getWidth();
        $cellHeight = $this->size / $matrix->getHeight();

        $this->pdf->SetFillColor($this->fgColor[0], $this->fgColor[1], $this->fgColor[2]);

        for ($x = 0; $x < $matrix->getWidth(); $x++) {
            for ($y = 0; $y < $matrix->getHeight(); $y++) {
                if ($matrix->get($x, $y)) {
                    $this->pdf->Rect($this->x + $x * $cellWidth, $this->y + $y * $cellHeight, $cellWidth, $cellHeight, 'F');
                }
            }
        }
    }
}
