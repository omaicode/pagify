<?php

namespace Pagify\Core\Wsre\Resolvers;

use Pagify\Core\Wsre\Contracts\WsreResolver;
use Pagify\Core\Wsre\Resolvers\PageBuilder\PageBuilderInstanceTreeRenderer;
use Pagify\Core\Wsre\Resolvers\PageBuilder\PageBuilderInterfaceSnapshotBuilder;
use Pagify\Core\Wsre\Resolvers\PageBuilder\PageBuilderStylesheetRenderer;
use Pagify\Core\Wsre\Resolvers\PageBuilder\PageBuilderVariableResolver;
use Pagify\Core\Wsre\WsreRenderContext;

class PageBuilderInterfaceResolver implements WsreResolver
{
	public const KEY = 'page-builder.render.interface';

	public function __construct(
		private readonly PageBuilderInterfaceSnapshotBuilder $snapshotBuilder,
		private readonly PageBuilderVariableResolver $variableResolver,
		private readonly PageBuilderStylesheetRenderer $stylesheetRenderer,
		private readonly PageBuilderInstanceTreeRenderer $instanceTreeRenderer,
	) {
	}

	public function key(): string
	{
		return self::KEY;
	}

	/**
	 * @param array<string, mixed> $params
	 */
	public function resolve(array $params, WsreRenderContext $context): mixed
	{
		$snapshot = $this->snapshotBuilder->build($params);
		if (! is_array($snapshot)) {
			return '';
		}

		$instances = is_array($snapshot['instances'] ?? null) ? $snapshot['instances'] : [];
		$propsByInstance = is_array($snapshot['propsByInstance'] ?? null) ? $snapshot['propsByInstance'] : [];
		$stylesList = is_array($snapshot['stylesList'] ?? null) ? $snapshot['stylesList'] : [];
		$sourceIdsByInstance = is_array($snapshot['sourceIdsByInstance'] ?? null) ? $snapshot['sourceIdsByInstance'] : [];
		$breakpointById = is_array($snapshot['breakpointById'] ?? null) ? $snapshot['breakpointById'] : [];
		$dataSourcesById = is_array($snapshot['dataSourcesById'] ?? null) ? $snapshot['dataSourcesById'] : [];
		$resourcesById = is_array($snapshot['resourcesById'] ?? null) ? $snapshot['resourcesById'] : [];
		$variableValuesRaw = is_array($snapshot['variableValuesRaw'] ?? null) ? $snapshot['variableValuesRaw'] : [];
		$interfaceHash = is_string($snapshot['interfaceHash'] ?? null) ? trim((string) $snapshot['interfaceHash']) : '';
		$rootId = trim((string) ($snapshot['rootId'] ?? ''));

		$variableValues = $this->variableResolver->compileValues($variableValuesRaw, $dataSourcesById, $resourcesById);

		$css = $this->stylesheetRenderer->render($stylesList, $sourceIdsByInstance, $breakpointById, $interfaceHash);
		$content = $this->instanceTreeRenderer->render($rootId, $instances, $propsByInstance, $variableValues);
		if ($content === '') {
			return '';
		}

		return $css !== '' ? ('<style>' . $css . '</style>' . $content) : $content;
	}
}
