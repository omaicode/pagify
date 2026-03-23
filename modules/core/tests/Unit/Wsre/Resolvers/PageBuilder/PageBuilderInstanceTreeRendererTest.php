<?php

namespace Pagify\Core\Tests\Unit\Wsre\Resolvers\PageBuilder;

use Pagify\Core\Wsre\Resolvers\PageBuilder\PageBuilderInstanceTreeRenderer;
use Pagify\Core\Wsre\Resolvers\PageBuilder\PageBuilderVariableResolver;
use PHPUnit\Framework\TestCase;

class PageBuilderInstanceTreeRendererTest extends TestCase
{
    public function test_render_outputs_html_from_instances_with_resolved_attributes(): void
    {
        $renderer = new PageBuilderInstanceTreeRenderer(new PageBuilderVariableResolver());

        $html = $renderer->render(
            rootId: 'root-1',
            instances: [
                'root-1' => [
                    'id' => 'root-1',
                    'component' => 'ws:element',
                    'tag' => 'body',
                    'children' => [
                        ['type' => 'id', 'value' => 'link-1'],
                    ],
                ],
                'link-1' => [
                    'id' => 'link-1',
                    'component' => 'ws:element',
                    'tag' => 'a',
                    'children' => [
                        ['type' => 'text', 'value' => 'Visit now'],
                    ],
                ],
            ],
            propsByInstance: [
                'link-1' => [
                    [
                        'name' => 'href',
                        'type' => 'parameter',
                        'value' => 'ds-link',
                    ],
                    [
                        'name' => 'hidden',
                        'type' => 'literal',
                        'value' => true,
                    ],
                ],
            ],
            variableValues: [
                'ds-link' => 'https://example.test/go',
            ],
        );

        $this->assertStringContainsString('<a href="https://example.test/go" hidden data-ws-id="link-1">Visit now</a>', $html);
        $this->assertStringNotContainsString('<body', $html);
    }

    public function test_render_skips_block_template_instances(): void
    {
        $renderer = new PageBuilderInstanceTreeRenderer(new PageBuilderVariableResolver());

        $html = $renderer->render(
            rootId: 'root-1',
            instances: [
                'root-1' => [
                    'id' => 'root-1',
                    'component' => 'ws:element',
                    'tag' => 'body',
                    'children' => [
                        ['type' => 'id', 'value' => 'template-1'],
                    ],
                ],
                'template-1' => [
                    'id' => 'template-1',
                    'component' => 'ws:block-template',
                    'tag' => null,
                    'children' => [
                        ['type' => 'text', 'value' => 'should not render'],
                    ],
                ],
            ],
            propsByInstance: [],
            variableValues: [],
        );

        $this->assertSame('', $html);
    }
}
