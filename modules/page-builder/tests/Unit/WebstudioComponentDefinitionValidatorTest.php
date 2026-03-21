<?php

namespace Pagify\PageBuilder\Tests\Unit;

use Pagify\PageBuilder\Services\WebstudioComponentDefinitionValidator;
use Tests\TestCase;

class WebstudioComponentDefinitionValidatorTest extends TestCase
{
	public function test_validator_fills_defaults_and_normalizes_values(): void
	{
		$validator = new WebstudioComponentDefinitionValidator();

		$normalized = $validator->validateAndNormalize([
			'key' => 'demo:cta_strip',
			'class' => ['cta', '', 'cta--inline'],
			'style' => [
				'padding' => '16px',
				'display' => 'flex',
			],
			'attributes' => [
				'data-active' => true,
				'role' => 'region',
			],
			'owner_type' => 'unknown',
		], 'unit-test');

		$this->assertIsArray($normalized);
		$this->assertSame('demo:cta_strip', $normalized['key']);
		$this->assertSame('Cta Strip', $normalized['label']);
		$this->assertSame('module', $normalized['owner_type']);
		$this->assertSame('cta cta--inline', $normalized['class']);
		$this->assertSame('padding: 16px; display: flex', $normalized['style']);
		$this->assertSame([
			'data-active' => 'data-active',
			'role' => 'region',
		], $normalized['attributes']);
		$this->assertSame('Registered Components', $normalized['category']);
	}

	public function test_validator_rejects_missing_or_invalid_key(): void
	{
		$validator = new WebstudioComponentDefinitionValidator();

		$this->assertNull($validator->validateAndNormalize([], 'unit-test-missing-key'));
		$this->assertNull($validator->validateAndNormalize(['key' => 'invalid key'], 'unit-test-invalid-key'));
	}

	public function test_validator_normalizes_children_nodes(): void
	{
		$validator = new WebstudioComponentDefinitionValidator();

		$normalized = $validator->validateAndNormalize([
			'key' => 'demo:composite',
			'children' => [
				'hero-banner',
				[
					'element' => 'div',
					'class' => ['child', 'child--inline'],
					'attributes' => [
						'data-active' => true,
					],
					'children' => [
						[
							'key' => 'demo:cta',
						],
					],
				],
			],
		], 'unit-test-children');

		$this->assertIsArray($normalized);
		$this->assertIsArray($normalized['children']);
		$this->assertSame('hero-banner', $normalized['children'][0]);
		$this->assertSame('div', $normalized['children'][1]['element']);
		$this->assertSame('child child--inline', $normalized['children'][1]['class']);
		$this->assertSame('data-active', $normalized['children'][1]['attributes']['data-active']);
		$this->assertSame('demo:cta', $normalized['children'][1]['children'][0]['key']);
	}
}
