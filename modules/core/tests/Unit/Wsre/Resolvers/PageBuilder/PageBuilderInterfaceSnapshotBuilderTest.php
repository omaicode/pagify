<?php

namespace Pagify\Core\Tests\Unit\Wsre\Resolvers\PageBuilder;

use Pagify\Core\Wsre\Resolvers\PageBuilder\PageBuilderInterfaceSnapshotBuilder;
use PHPUnit\Framework\TestCase;

class PageBuilderInterfaceSnapshotBuilderTest extends TestCase
{
    public function test_build_returns_compiled_snapshot_with_root_and_hash(): void
    {
        $builder = new PageBuilderInterfaceSnapshotBuilder();

        $params = [
            'page_id' => 11,
            'interface' => [
                'instances' => [
                    [
                        'id' => 'root-11',
                        'component' => 'ws:element',
                        'tag' => 'body',
                        'children' => [
                            ['type' => 'id', 'value' => 'child-11'],
                        ],
                    ],
                    [
                        'id' => 'child-11',
                        'component' => 'ws:element',
                        'tag' => 'a',
                        'children' => [],
                    ],
                ],
                'props' => [
                    [
                        'instanceId' => 'child-11',
                        'name' => 'href',
                        'type' => 'parameter',
                        'value' => 'ds-link',
                    ],
                ],
                'styles' => [
                    [
                        'styleSourceId' => 'ss-child',
                        'breakpointId' => 'base',
                        'property' => 'marginTop',
                        'value' => '12px',
                    ],
                ],
                'styleSourceSelections' => [
                    [
                        'instanceId' => 'child-11',
                        'values' => ['ss-child'],
                    ],
                ],
                'breakpoints' => [
                    ['id' => 'base', 'maxWidth' => null],
                ],
                'dataSources' => [
                    ['id' => 'ds-link', 'type' => 'variable'],
                ],
                'resources' => [
                    ['id' => 'res-1', 'url' => 'https://example.test'],
                ],
                'variableValues' => [
                    'ds-link' => 'https://example.test/link',
                ],
            ],
        ];

        $snapshot = $builder->build($params);

        $this->assertIsArray($snapshot);
        $this->assertSame('root-11', $snapshot['rootId']);
        $this->assertIsString($snapshot['interfaceHash']);
        $this->assertSame(40, strlen($snapshot['interfaceHash']));
        $this->assertArrayHasKey('child-11', $snapshot['propsByInstance']);
        $this->assertArrayHasKey('child-11', $snapshot['sourceIdsByInstance']);
        $this->assertArrayHasKey('base', $snapshot['breakpointById']);
        $this->assertArrayHasKey('ds-link', $snapshot['dataSourcesById']);
        $this->assertSame('https://example.test/link', $snapshot['variableValuesRaw']['ds-link']);
    }

    public function test_build_returns_null_when_interface_or_instances_are_missing(): void
    {
        $builder = new PageBuilderInterfaceSnapshotBuilder();

        $this->assertNull($builder->build([]));
        $this->assertNull($builder->build(['interface' => ['instances' => []]]));
    }
}
