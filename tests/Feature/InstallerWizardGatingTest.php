<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class InstallerWizardGatingTest extends TestCase
{
    private string $stateFile;

    private string $installedFile;

    private string $lockFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stateFile = storage_path('framework/testing/installer-gating-state.json');
        $this->installedFile = storage_path('framework/testing/installer-gating-installed.lock');
        $this->lockFile = storage_path('framework/testing/installer-gating-install.lock');

        config()->set('installer.guard.enable_in_testing', true);
        config()->set('installer.state.state_file', $this->stateFile);
        config()->set('installer.state.installed_file', $this->installedFile);
        config()->set('installer.state.lock_file', $this->lockFile);

        File::delete([$this->stateFile, $this->installedFile, $this->lockFile]);

        File::ensureDirectoryExists(dirname(database_path('database.sqlite')));

        if (! is_file(database_path('database.sqlite'))) {
            File::put(database_path('database.sqlite'), '');
        }
    }

    protected function tearDown(): void
    {
        File::delete([$this->stateFile, $this->installedFile, $this->lockFile]);

        parent::tearDown();
    }

    public function test_cannot_access_plugins_before_purpose_step_is_completed(): void
    {
        $this->getJson('/api/v1/install/plugins')
            ->assertStatus(422)
            ->assertJsonPath('code', 'INSTALLER_STEP_BLOCKED');
    }

    public function test_configuration_step_fails_without_completed_system_checks(): void
    {
        $payload = [
            'project_name' => 'Pagify',
            'app_url' => 'http://localhost',
            'db' => [
                'connection' => 'sqlite',
                'database' => database_path('database.sqlite'),
            ],
            'mail' => [
                'mailer' => 'log',
            ],
            'admin' => [
                'name' => 'Admin',
                'username' => 'admin',
                'email' => 'admin@example.com',
                'password' => 'password123',
            ],
        ];

        $this->postJson('/api/v1/install/configuration', $payload)
            ->assertStatus(422)
            ->assertJsonPath('code', 'INSTALLER_STEP_BLOCKED');
    }

    public function test_purpose_step_requires_configuration_step(): void
    {
        $this->postJson('/api/v1/install/purpose', [
            'purpose' => 'blog',
        ])->assertStatus(422)
            ->assertJsonPath('code', 'INSTALLER_STEP_BLOCKED');
    }

    public function test_configuration_failure_does_not_persist_configuration_state(): void
    {
        File::put($this->stateFile, json_encode([
            'installed' => false,
            'current_step' => 2,
            'completed_steps' => [1],
            'configuration' => [],
        ], JSON_PRETTY_PRINT));

        $payload = [
            'project_name' => 'Pagify',
            'app_url' => 'http://localhost',
            'db' => [
                'connection' => 'sqlite',
                'database' => database_path('database.sqlite'),
            ],
            'mail' => [
                'mailer' => 'smtp',
                'host' => '127.0.0.1',
                'port' => 1,
            ],
            'admin' => [
                'name' => 'Admin',
                'username' => 'admin',
                'email' => 'admin@example.com',
                'password' => 'password123',
            ],
        ];

        $this->postJson('/api/v1/install/configuration', $payload)
            ->assertStatus(422)
            ->assertJsonPath('code', 'MAIL_CONNECTION_FAILED');

        $this->getJson('/api/v1/install/state')
            ->assertOk()
            ->assertJsonPath('data.configuration', []);
    }

    public function test_finalize_cannot_be_called_before_previous_steps_complete(): void
    {
        $this->postJson('/api/v1/install/finalize', [])
            ->assertStatus(422)
            ->assertJsonPath('code', 'INSTALLER_STEP_BLOCKED');
    }
}
