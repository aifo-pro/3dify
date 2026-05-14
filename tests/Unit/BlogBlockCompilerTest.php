<?php

namespace Tests\Unit;

use App\Services\BlogBlockCompiler;
use PHPUnit\Framework\TestCase;

class BlogBlockCompilerTest extends TestCase
{
    public function test_compiles_heading_and_divider(): void
    {
        $c = new BlogBlockCompiler;
        $html = $c->compile([
            'version' => 1,
            'blocks' => [
                [
                    'type' => 'heading',
                    'uk' => ['text' => 'Тест', 'level' => 2],
                    'en' => ['text' => 'Test', 'level' => 2],
                ],
                ['type' => 'divider', 'uk' => [], 'en' => []],
            ],
        ]);

        $this->assertStringContainsString('<h2>Тест</h2>', $html['uk']);
        $this->assertStringContainsString('<hr>', $html['uk']);
        $this->assertStringContainsString('<h2>Test</h2>', $html['en']);
    }
}
