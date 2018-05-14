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

    public function __construct(\FPDF $fpdfLikeObject = null, $size = false, $x = false, $y = false, $fgColor = array(0, 0, 0), $bgColor = array(256, 256, 256))
    {
        $this->fgColor = $fgColor;
        $this->bgColor = $bgColor;
        $this->pdf = $fpdfLikeObject;
        $this->x = $x;
        $this->y = $y;
        $this->s = $s;
    }
    public function setFPDF(FPDF $fpdfLikeObject){
        $this->pdf = $fpdfLikeObject;
    }
    public function setSizeAndOffset($s,$x,$y){
        $this->x = $x;
        $this->y = $y;
        $this->s = $s;
    }
    private function parseColor($color)
    {
        if (!is_array($color) || count($color) != 3) {
            throw new \InvalidArgumentException('Color array does not have three components');
        }
        for ($i = 0; $i < count($color); $i++) {
            $color[$i] = intval($color[$i]);
        }
        return $color;
    }

    public function render(AztecCode $code)
    {
        if(!$this->pdf){
            throw new \InvalidArgumentException('FPDF was not set yet. Please set the FPDF object using setFPDF() or in the constructor.');
        }
        if($this->x===false||$this->y===false||$this->s===false){
            throw new \InvalidArgumentException('Size and offsets were not set yet. Please set the size and offsets using setSizeAndOffset() or in the constructor.');
        }
        $matrix = $code->getMatrix();       
        $fg = $this->parseColor($this->fgColor);
        $bg = $this->parseColor($this->bgColor);

        if($bg[0]<256&&$bg[1]<256&&$bg[2]<256){
            // Background
            $this->pdf->SetFillColor($bg[0],$bg[1],$bg[2]);
            // Make background
            $this->pdf->Rect($this->x, $this->y, $this->s, $this->s, 'F' );
        }

        $cellsize_w = $this->s/$matrix->getWidth();
        $cellsize_h = $this->s/$matrix->getHeight();

        // Foreground
        $this->pdf->SetFillColor($fg[0],$fg[1],$fg[2]);

        for ($x = 0; $x < $matrix->getWidth(); $x++) {
            for ($y = 0; $y < $matrix->getHeight(); $y++) {
                if ($matrix->get($x, $y)) {
                    $this->pdf->Rect($this->x+$x*$cellsize_w, $this->y+$y*$cellsize_h, $cellsize_w, $cellsize_h, 'F' );
                }
            }
        }
        return true;       
    }
}