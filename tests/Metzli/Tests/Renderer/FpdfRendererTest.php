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

namespace Metzli\Tests\Renderer;

use FPDF;
use Metzli\Encoder\Encoder;
use Metzli\Renderer\FpdfRenderer;
use Metzli\Tests\TestCase;

class FpdfRendererTest extends Testcase
{
    public function testRender()
    {
        $code = Encoder::encode('Hello World!');
        $path = tempnam(sys_get_temp_dir(), 'metzli');

        try {
            $pdf = new FPDF('P', 'mm', 'A4');
            $pdf->AddPage();

            $renderer = new FpdfRenderer($pdf, 10, 10, 100, [255, 0, 0], [0, 0, 255]);
            $renderer->render($code);

            $pdf->Output('F', $path);
            $this->assertSame('application/pdf', mime_content_type($path));
        } finally {
            @unlink($path);
        }
    }
}
