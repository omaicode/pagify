<?php

namespace Pagify\Core\Tests\Unit\Wsre\Resolvers\PageBuilder;

use Pagify\Core\Wsre\Resolvers\PageBuilder\PageBuilderVariableResolver;
use PHPUnit\Framework\TestCase;

class PageBuilderVariableResolverTest extends TestCase
{
    public function test_compile_values_merges_variable_and_resource_sources(): void
    {
        $resolver = new PageBuilderVariableResolver();

        $compiled = $resolver->compileValues(
            variableValuesRaw: ['preset' => 'ready'],
            dataSourcesById: [
                'var-1' => [
                    'type' => 'variable',
                    'value' => ['value' => 'from-data-source'],
                ],
                'res-ds' => [
                    'type' => 'resource',
                    'resourceId' => 'res-1',
                ],
            ],
            resourcesById: [
                'res-1' => [
                    'name' => 'Users API',
                    'url' => 'https://example.test/users',
                    'method' => 'GET',
                ],
            ],
        );

        $this->assertSame('ready', $compiled['preset']);
        $this->assertSame('from-data-source', $compiled['var-1']);
        $this->assertIsArray($compiled['res-ds']);
        $this->assertSame('https://example.test/users', $compiled['res-ds']['url']);
    }

    public function test_resolve_prop_value_handles_parameter_and_expression(): void
    {
        $resolver = new PageBuilderVariableResolver();
        $values = [
            'param-id' => 'https://example.test/param',
            'expr-id' => 'https://example.test/expression',
        ];

        $this->assertSame(
            'https://example.test/param',
            $resolver->resolvePropValue([
                'type' => 'parameter',
                'value' => 'param-id',
            ], $values)
        );

        $this->assertSame(
            'https://example.test/expression',
            $resolver->resolvePropValue([
                'type' => 'expression',
                'value' => '$ws$dataSource$expr__DASH__id',
            ], $values)
        );
    }
}
