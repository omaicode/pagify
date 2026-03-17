<?php

namespace Pagify\Updater\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Pagify\Updater\Services\ComposerCommandRunner;
use Tests\TestCase;

class UpdaterOutputSanitizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_composer_command_runner_strips_ansi_sequences_from_output_and_error_output(): void
    {
        config()->set('updater.composer.bin', 'php');
        config()->set('updater.composer.extra_args', []);

        $runner = app(ComposerCommandRunner::class);

        $result = $runner->run([
            '-r',
            'fwrite(STDOUT, "\033[90m.\033[39m CLEAN\n"); fwrite(STDERR, "\033[31mERR\033[0m\n");',
        ]);

        $this->assertTrue($result['ok']);
        $this->assertSame('. CLEAN'.PHP_EOL, $result['output']);
        $this->assertSame('ERR'.PHP_EOL, $result['error_output']);
        $this->assertStringNotContainsString("\033", $result['output']);
        $this->assertStringNotContainsString("\033", $result['error_output']);
    }
}
