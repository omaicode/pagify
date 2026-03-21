<?php

namespace Pagify\PageBuilder\Console\Commands;

use Illuminate\Console\Command;
use Pagify\PageBuilder\Services\WebstudioComponentRegistryAuditService;

class ValidateWebstudioComponentsCommand extends Command
{
	protected $signature = 'cms:page-builder:validate-webstudio-components {--json : Output machine-readable JSON report}';

	protected $description = 'Validate webstudio_components registrations across all modules and plugins.';

	public function handle(WebstudioComponentRegistryAuditService $audit): int
	{
		$report = $audit->auditAll();

		if ((bool) $this->option('json')) {
			$this->line((string) json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

			return $report['ok'] ? self::SUCCESS : self::FAILURE;
		}

		$this->info(sprintf('Checked: %d, valid: %d, issues: %d', $report['checked'], $report['valid'], count($report['issues'])));

		if ($report['ok']) {
			$this->info('All webstudio components are valid.');

			return self::SUCCESS;
		}

		$this->error('Invalid webstudio component registrations found:');
		foreach ($report['issues'] as $issue) {
			$this->line(sprintf(
				'- [%s:%s] %s => %s',
				$issue['owner_type'] ?? 'unknown',
				$issue['owner'] ?? 'unknown',
				$issue['class'] ?? '-',
				$issue['error'] ?? 'Unknown error'
			));
		}

		return self::FAILURE;
	}
}
