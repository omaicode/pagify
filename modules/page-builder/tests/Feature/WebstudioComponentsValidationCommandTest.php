<?php

namespace Pagify\PageBuilder\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class WebstudioComponentsValidationCommandTest extends TestCase
{
	private string $tempModuleRoot;

	protected function setUp(): void
	{
		parent::setUp();

		$this->tempModuleRoot = base_path('modules/temp-webstudio-validation');
	}

	protected function tearDown(): void
	{
		if (is_dir($this->tempModuleRoot)) {
			File::deleteDirectory($this->tempModuleRoot);
		}

		parent::tearDown();
	}

	public function test_command_passes_for_current_workspace_components(): void
	{
		$exitCode = Artisan::call('cms:page-builder:validate-webstudio-components');

		$this->assertSame(0, $exitCode);
	}

	public function test_command_fails_when_class_does_not_implement_required_interface(): void
	{
		File::ensureDirectoryExists($this->tempModuleRoot . '/config');
		File::put($this->tempModuleRoot . '/config/module.php', <<<'PHP'
<?php

return [
	'enabled' => true,
	'name' => 'Temp Validation Module',
	'slug' => 'temp-webstudio-validation',
	'config_key' => 'temp-webstudio-validation',
	'webstudio_components' => [
		\stdClass::class,
	],
];
PHP
		);

		$this->refreshApplication();

		$exitCode = Artisan::call('cms:page-builder:validate-webstudio-components');
		$output = Artisan::output();

		$this->assertSame(1, $exitCode);
		$this->assertStringContainsString('must implement', $output);
	}
}
