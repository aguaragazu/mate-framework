<?php

namespace Mate\Tests\View;

use Mate\View\MateEngine;
use PHPUnit\Framework\TestCase;

class MateEngineTest extends TestCase {
    public function testRendersTemplateWithParameters() {
        $parameter1 = "Test 1";
        $parameter2 = 2;

        $engine = new MateEngine(__DIR__."/test-views");

        $content = $engine->render("view", compact('parameter1', 'parameter2'), "layout");

        $expected = "
            <html>
                <body>
                    <h1>$parameter1</h1>
                    <h2>$parameter2</h2>
                </body>
            </html>
        ";


        $this->assertEquals(
            preg_replace("/\s*/m", "", $expected),
            preg_replace("/\s*/m", "", $content)
        );
    }
}
