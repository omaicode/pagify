<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class InstallerBootstrapFlowTest extends TestCase
{
    private string $stateFile;

    private string $installedFile;

    private string $lockFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stateFile = storage_path('framework/testing/installer-state.json');
        $this->installedFile = storage_path('framework/testing/installer-installed.lock');
        $this->lockFile = storage_path('framework/testing/installer-install.lock');

        config()->set('installer.guard.enable_in_testing', true);
        config()->set('installer.state.state_file', $this->stateFile);
        config()->set('installer.state.installed_file', $this->installedFile);
        config()->set('installer.state.lock_file', $this->lockFile);

        File::delete([$this->stateFile, $this->installedFile, $this->lockFile]);
    }

    protected function tearDown(): void
    {
        File::delete([$this->stateFile, $this->installedFile, $this->lockFile]);

        parent::tearDown();
    }

    public function test_home_redirects_to_install_when_not_installed(): void
    {
        $this->get('/')
            ->assertRedirect('/install');
    }

    public function test_install_page_is_accessible_when_not_installed(): void
    {
        $this->get('/install')
            ->assertOk()
            ->assertSee('Pagify Installer');
    }

    public function test_non_installer_api_request_is_blocked_until_installed(): void
    {
        $this->getJson('/api/v1/health')
            ->assertStatus(423)
            ->assertJsonPath('code', 'INSTALLATION_REQUIRED');
    }

    public function test_health_endpoint_is_not_redirected_when_not_installed(): void
    {
        $response = $this->get('/up');

        $response->assertStatus(200);
    }

    public function test_install_routes_are_blocked_after_installation_is_completed(): void
    {
        File::put($this->installedFile, now()->toIso8601String());

        $this->get('/install')
            ->assertRedirect('/');

        $this->getJson('/api/v1/install/state')
            ->assertStatus(403)
            ->assertJsonPath('code', 'INSTALLATION_ALREADY_COMPLETED');
    }
}
