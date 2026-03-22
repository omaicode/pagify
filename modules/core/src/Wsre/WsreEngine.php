<?php

namespace Pagify\Core\Wsre;

use Pagify\Core\Services\FrontendThemeManifestService;
use Pagify\Core\Support\SiteContext;

class WsreEngine
{
	public function __construct(
		private readonly FrontendThemeManifestService $manifestService,
		private readonly WsreResolverRegistry $resolverRegistry,
		private readonly SiteContext $siteContext,
	) {
	}

	/**
	 * @param array<int, string> $themePaths
	 * @param array<string, mixed> $globals
	 */
	public function render(array $themePaths, string $requestPath, array $globals = []): ?string
	{
		foreach ($themePaths as $themePath) {
			if (! is_string($themePath) || trim($themePath) === '') {
				continue;
			}

			$themePath = rtrim($themePath, '/\\');
			$manifest = $this->manifestService->readManifestFile($themePath . '/theme.json');
			$manifestData = is_array($manifest['manifest']) ? $manifest['manifest'] : null;
			if (! is_array($manifestData)) {
				continue;
			}

			$engine = strtolower(trim((string) ($manifestData['render']['engine'] ?? '')));
			if ($engine !== 'wsre') {
				continue;
			}

			$templatePath = $this->resolveTemplatePath($themePath, $requestPath);
			if ($templatePath === null) {
				continue;
			}

			$decoded = json_decode((string) file_get_contents($templatePath), true);
			if (! is_array($decoded)) {
				continue;
			}

			$context = new WsreRenderContext(
				requestPath: trim($requestPath, '/'),
				siteId: $this->siteContext->siteId(),
				globals: $globals,
			);

			return $this->renderDocument($decoded, $context);
		}

		return null;
	}

	private function resolveTemplatePath(string $themePath, string $requestPath): ?string
	{
		$normalized = trim($requestPath, '/');
		$candidates = [];

		if ($normalized === '') {
			$candidates = ['pages/home.json', 'pages/index.json'];
		} else {
			$candidates = [
				'pages/' . $normalized . '.json',
				'pages/' . $normalized . '/index.json',
			];
		}

		foreach ($candidates as $candidate) {
			$absolute = $themePath . '/' . str_replace('/', DIRECTORY_SEPARATOR, $candidate);
			if (is_file($absolute)) {
				return $absolute;
			}
		}

		return null;
	}

	/**
	 * @param array<string, mixed> $document
	 */
	private function renderDocument(array $document, WsreRenderContext $context): string
	{
		if (is_string($document['html'] ?? null) && trim((string) $document['html']) !== '') {
			return (string) $document['html'];
		}

		$headHtml = $this->renderNodes($document['head'] ?? [], $context);
		$bodySource = $document['body'] ?? $document['content'] ?? [];
		$bodyHtml = $this->renderNodes($bodySource, $context);

		$layoutHtml = is_string($document['layout_html'] ?? null) ? (string) $document['layout_html'] : '';
		if ($layoutHtml !== '') {
			return str_replace(['{{ head }}', '{{ content }}'], [$headHtml, $bodyHtml], $layoutHtml);
		}

		return '<!doctype html><html><head>' . $headHtml . '</head><body>' . $bodyHtml . '</body></html>';
	}

	private function renderNodes(mixed $nodes, WsreRenderContext $context): string
	{
		if (is_string($nodes)) {
			return $nodes;
		}

		if (! is_array($nodes)) {
			return '';
		}

		if ($this->isAssoc($nodes)) {
			return $this->renderNode($nodes, $context);
		}

		$html = '';
		foreach ($nodes as $node) {
			if (is_array($node)) {
				$html .= $this->renderNode($node, $context);
			} elseif (is_string($node)) {
				$html .= $node;
			}
		}

		return $html;
	}

	/**
	 * @param array<string, mixed> $node
	 */
	private function renderNode(array $node, WsreRenderContext $context): string
	{
		$dynamic = $node['dynamic'] ?? null;
		if (is_array($dynamic)) {
			return $this->renderDynamicNode($dynamic, $context);
		}

		if (isset($node['resolver']) && is_string($node['resolver'])) {
			return $this->renderDynamicNode([
				'resolver' => $node['resolver'],
				'params' => is_array($node['params'] ?? null) ? $node['params'] : [],
			], $context);
		}

		$tag = trim((string) ($node['tag'] ?? ''));
		if ($tag === '') {
			return '';
		}

		$attrs = is_array($node['attrs'] ?? null) ? $node['attrs'] : [];
		$attrTokens = [];
		foreach ($attrs as $name => $value) {
			if (! is_string($name) || trim($name) === '') {
				continue;
			}

			if (is_bool($value)) {
				if ($value) {
					$attrTokens[] = trim($name);
				}
				continue;
			}

			if (is_scalar($value)) {
				$attrTokens[] = trim($name) . '="' . e((string) $value) . '"';
			}
		}
		$attrString = $attrTokens === [] ? '' : ' ' . implode(' ', $attrTokens);

		if (is_string($node['html'] ?? null)) {
			$content = (string) $node['html'];
		} else {
			$text = is_scalar($node['text'] ?? null) ? (string) $node['text'] : '';
			$content = $text !== '' ? e($text) : '';
			$content .= $this->renderNodes($node['children'] ?? [], $context);
		}

		return '<' . $tag . $attrString . '>' . $content . '</' . $tag . '>';
	}

	/**
	 * @param array<string, mixed> $dynamic
	 */
	private function renderDynamicNode(array $dynamic, WsreRenderContext $context): string
	{
		$resolverKey = trim((string) ($dynamic['resolver'] ?? ''));
		if ($resolverKey === '') {
			return '';
		}

		$params = is_array($dynamic['params'] ?? null) ? $dynamic['params'] : [];
		$resolved = $this->resolverRegistry->resolve($resolverKey, $params, $context);

		if (is_string($resolved)) {
			return $resolved;
		}

		if (is_scalar($resolved)) {
			return e((string) $resolved);
		}

		if (is_array($resolved) && isset($resolved['html']) && is_string($resolved['html'])) {
			return $resolved['html'];
		}

		if (is_array($resolved)) {
			return $this->renderNodes($resolved, $context);
		}

		return '';
	}

	/**
	 * @param array<mixed> $value
	 */
	private function isAssoc(array $value): bool
	{
		if ($value === []) {
			return false;
		}

		return array_keys($value) !== range(0, count($value) - 1);
	}
}
