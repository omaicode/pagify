<?php

namespace Pagify\PageBuilder\Services;

class PageDiffService
{
	/**
	 * @param array<string, mixed> $before
	 * @param array<string, mixed> $after
	 * @return array<string, mixed>
	 */
	public function diff(array $before, array $after): array
	{
		$changes = [];

		foreach (['title', 'slug'] as $key) {
			$beforeValue = $before[$key] ?? null;
			$afterValue = $after[$key] ?? null;

			if ($beforeValue !== $afterValue) {
				$changes[] = [
					'key' => $key,
					'before' => $beforeValue,
					'after' => $afterValue,
				];
			}
		}

		$this->compareNested('layout', (array) ($before['layout'] ?? []), (array) ($after['layout'] ?? []), $changes);
		$this->compareNested('seo', (array) ($before['seo'] ?? []), (array) ($after['seo'] ?? []), $changes);

		return [
			'changed' => $changes !== [],
			'changes' => $changes,
		];
	}

	/**
	 * @param array<string, mixed> $before
	 * @param array<string, mixed> $after
	 * @param array<int, array<string, mixed>> $changes
	 */
	private function compareNested(string $prefix, array $before, array $after, array &$changes): void
	{
		$keys = array_values(array_unique([...array_keys($before), ...array_keys($after)]));

		foreach ($keys as $key) {
			$beforeValue = $before[$key] ?? null;
			$afterValue = $after[$key] ?? null;

			if ($beforeValue === $afterValue) {
				continue;
			}

			if (is_array($beforeValue) && is_array($afterValue)) {
				$this->compareNested($prefix . '.' . $key, $beforeValue, $afterValue, $changes);
				continue;
			}

			$changes[] = [
				'key' => $prefix . '.' . $key,
				'before' => $beforeValue,
				'after' => $afterValue,
			];
		}
	}
}
