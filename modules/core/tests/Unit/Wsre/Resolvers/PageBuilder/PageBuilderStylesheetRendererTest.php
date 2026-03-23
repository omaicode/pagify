<?php

namespace Pagify\Core\Tests\Unit\Wsre\Resolvers\PageBuilder;

use Pagify\Core\Wsre\Resolvers\PageBuilder\PageBuilderStylesheetRenderer;
use PHPUnit\Framework\TestCase;

class PageBuilderStylesheetRendererTest extends TestCase
{
    public function test_render_generates_css_rules_with_media_queries(): void
    {
        $renderer = new PageBuilderStylesheetRenderer();

        $css = $renderer->render(
            styles: [
                [
                    'styleSourceId' => 'source-1',
                    'breakpointId' => '',
                    'property' => 'marginTop',
                    'value' => '8px',
                ],
                [
                    'styleSourceId' => 'source-1',
                    'breakpointId' => 'bp-mobile',
                    'property' => 'display',
                    'value' => ['type' => 'keyword', 'value' => 'none'],
                ],
            ],
            sourceIdsByInstance: [
                'instance-1' => ['source-1'],
            ],
            breakpointById: [
                'bp-mobile' => ['id' => 'bp-mobile', 'maxWidth' => 767],
            ],
        );

        $this->assertStringContainsString('[data-ws-id="instance-1"]{margin-top:8px;}', $css);
        $this->assertStringContainsString('@media (max-width:767px)', $css);
        $this->assertStringContainsString('display:none;', $css);
    }

    public function test_render_uses_interface_hash_cache_when_provided(): void
    {
        $renderer = new PageBuilderStylesheetRenderer();
        $hash = sha1('same-interface');

        $first = $renderer->render(
            styles: [[
                'styleSourceId' => 'source-1',
                'breakpointId' => '',
                'property' => 'color',
                'value' => 'red',
            ]],
            sourceIdsByInstance: [
                'instance-1' => ['source-1'],
            ],
            breakpointById: [],
            interfaceHash: $hash,
        );

        $second = $renderer->render(
            styles: [[
                'styleSourceId' => 'source-2',
                'breakpointId' => '',
                'property' => 'color',
                'value' => 'blue',
            ]],
            sourceIdsByInstance: [
                'instance-2' => ['source-2'],
            ],
            breakpointById: [],
            interfaceHash: $hash,
        );

        $this->assertSame($first, $second);
        $this->assertStringContainsString('color:red;', $second);
        $this->assertStringNotContainsString('color:blue;', $second);
    }
}
