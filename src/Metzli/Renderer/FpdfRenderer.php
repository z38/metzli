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

use Metzli\Encoder\AztecCode;

class FpdfRenderer implements RendererInterface
{
    private $fgColor;
    private $bgColor;
    private $pdf;

    private $x;
    private $y;

    private $s;

    public function __construct(\FPDF $fpdfLikeObject, $s, $x, $y, $fgColor = array(0, 0, 0), $bgColor = null)
    {
        $this->fgColor = $fgColor;
        $this->bgColor = $bgColor;
        $this->pdf = $fpdfLikeObject;
        $this->x = $x;
        $this->y = $y;
        $this->s = $s;
    }

    public function render(AztecCode $code)
    {        
        $matrix = $code->getMatrix();       

        if($this->bgColor!=null){
            // Background
            $this->pdf->SetFillColor($this->bgColor[0],$this->bgColor[1],$this->bgColor[2]);
            // Make background
            $this->pdf->Rect($this->x, $this->y, $this->s, $this->s, 'F' );
        }

        $cellsize_w = $this->s/$matrix->getWidth();
        $cellsize_h = $this->s/$matrix->getHeight();

        // Foreground
        $this->pdf->SetFillColor($this->fgColor[0],$this->fgColor[1],$this->fgColor[2]);

        for ($x = 0; $x < $matrix->getWidth(); $x++) {
            for ($y = 0; $y < $matrix->getHeight(); $y++) {
                if ($matrix->get($x, $y)) {
                    $this->pdf->Rect($this->x+$x*$cellsize_w, $this->y+$y*$cellsize_h, $cellsize_w, $cellsize_h, 'F' );
                }
            }
        }      
    }
}