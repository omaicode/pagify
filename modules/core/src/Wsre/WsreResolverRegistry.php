<?php

namespace Pagify\Core\Wsre;

use Illuminate\Contracts\Container\Container;
use Pagify\Core\Services\EventBus;
use Pagify\Core\Wsre\Contracts\WsreResolver;
use Pagify\Core\Wsre\Resolvers\CoreUsersListResolver;
use Pagify\Core\Wsre\Resolvers\PageBuilderInterfaceResolver;

class WsreResolverRegistry
{
	/**
	 * @var array<string, callable>|null
	 */
	private ?array $compiledResolvers = null;

	public function __construct(
		private readonly EventBus $eventBus,
		private readonly Container $container,
	) {
	}

	/**
	 * @param array<string, mixed> $params
	 * @return mixed
	 */
	public function resolve(string $resolverKey, array $params, WsreRenderContext $context): mixed
	{
		$resolver = $this->resolvers()[$resolverKey] ?? null;
		if (! is_callable($resolver)) {
			return null;
		}

		return $resolver($params, $context);
	}

	/**
	 * @return array<string, callable>
	 */
	private function resolvers(): array
	{
		if (is_array($this->compiledResolvers)) {
			return $this->compiledResolvers;
		}

		$compiled = [];
		foreach ($this->defaultResolvers() as $resolver) {
			$compiled[$resolver->key()] = fn (array $params, WsreRenderContext $context): mixed => $resolver->resolve($params, $context);
		}

		$contributions = $this->eventBus->emitHook(WsreHooks::RESOLVERS);
		foreach ($contributions as $contribution) {
			foreach ($this->normalizeContribution($contribution) as $key => $resolver) {
				$compiled[$key] = $resolver;
			}
		}

		$this->compiledResolvers = $compiled;

		return $compiled;
	}

	/**
	 * @return array<int, WsreResolver>
	 */
	private function defaultResolvers(): array
	{
		return [
			$this->container->make(CoreUsersListResolver::class),
			$this->container->make(PageBuilderInterfaceResolver::class),
		];
	}

	/**
	 * @return array<string, callable>
	 */
	private function normalizeContribution(mixed $contribution): array
	{
		if ($contribution instanceof WsreResolver) {
			return [
				$contribution->key() => fn (array $params, WsreRenderContext $context): mixed => $contribution->resolve($params, $context),
			];
		}

		if (is_string($contribution) && class_exists($contribution) && is_subclass_of($contribution, WsreResolver::class)) {
			/** @var WsreResolver $resolver */
			$resolver = $this->container->make($contribution);

			return [
				$resolver->key() => fn (array $params, WsreRenderContext $context): mixed => $resolver->resolve($params, $context),
			];
		}

		if (! is_array($contribution)) {
			return [];
		}

		$normalized = [];
		foreach ($contribution as $key => $value) {
			if (is_int($key)) {
				if (is_array($value) && isset($value['key']) && isset($value['resolver']) && is_string($value['key'])) {
					$resolver = $this->normalizeResolverValue($value['resolver']);
					if ($resolver !== null) {
						$normalized[trim($value['key'])] = $resolver;
					}
					continue;
				}

				foreach ($this->normalizeContribution($value) as $nestedKey => $nestedResolver) {
					$normalized[$nestedKey] = $nestedResolver;
				}

				continue;
			}

			if (! is_string($key) || trim($key) === '') {
				continue;
			}

			$resolver = $this->normalizeResolverValue($value);
			if ($resolver !== null) {
				$normalized[trim($key)] = $resolver;
			}
		}

		return $normalized;
	}

	private function normalizeResolverValue(mixed $value): ?callable
	{
		if ($value instanceof WsreResolver) {
			return fn (array $params, WsreRenderContext $context): mixed => $value->resolve($params, $context);
		}

		if (is_string($value) && class_exists($value) && is_subclass_of($value, WsreResolver::class)) {
			/** @var WsreResolver $resolver */
			$resolver = $this->container->make($value);

			return fn (array $params, WsreRenderContext $context): mixed => $resolver->resolve($params, $context);
		}

		if (is_callable($value)) {
			return function (array $params, WsreRenderContext $context) use ($value): mixed {
				return $value($params, $context);
			};
		}

		return null;
	}
}
