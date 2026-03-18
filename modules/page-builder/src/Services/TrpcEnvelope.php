<?php

namespace Pagify\PageBuilder\Services;

class TrpcEnvelope
{
	/**
	 * @param  array<string, mixed>  $data
	 * @return array<string, mixed>
	 */
	public static function success(array $data): array
	{
		return [
			'result' => [
				'data' => $data,
			],
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function error(string $message, int $httpStatus, string $code): array
	{
		return [
			'error' => [
				'json' => [
					'message' => $message,
					'code' => -32603,
					'data' => [
						'code' => $code,
						'httpStatus' => $httpStatus,
					],
				],
			],
		];
	}
}
