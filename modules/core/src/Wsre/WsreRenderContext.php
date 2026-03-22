<?php

namespace Pagify\Core\Wsre;

class WsreRenderContext
{
	/**
	 * @param array<string, mixed> $globals
	 */
	public function __construct(
		private readonly string $requestPath,
		private readonly ?int $siteId,
		private readonly array $globals = [],
	) {
	}

	public function requestPath(): string
	{
		return $this->requestPath;
	}

	public function siteId(): ?int
	{
		return $this->siteId;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function globals(): array
	{
		return $this->globals;
	}
}
