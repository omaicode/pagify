<?php

namespace Pagify\Updater\Services;

use Symfony\Component\Process\Process;

class ComposerCommandRunner
{
    /**
     * @param array<int, string> $args
     * @return array{ok: bool, command: array<int, string>, exit_code: int, output: string, error_output: string}
     */
    public function run(array $args): array
    {
        $binary = (string) config('updater.composer.bin', 'composer');
        $extraArgs = config('updater.composer.extra_args', []);
        $timeout = (int) config('updater.composer.timeout_seconds', 900);
        $workingDir = (string) config('updater.composer.working_dir', base_path());

        $command = array_merge([$binary], $args, is_array($extraArgs) ? $extraArgs : []);

        $process = new Process($command, $workingDir);
        $process->setTimeout($timeout > 0 ? $timeout : null);
        $process->run();

        $output = $this->sanitizeOutput($process->getOutput());
        $errorOutput = $this->sanitizeOutput($process->getErrorOutput());

        return [
            'ok' => $process->isSuccessful(),
            'command' => $command,
            'exit_code' => $process->getExitCode() ?? 1,
            'output' => $output,
            'error_output' => $errorOutput,
        ];
    }

    private function sanitizeOutput(string $value): string
    {
        $withoutAnsi = preg_replace('/\e\[[\x30-\x3F]*[\x20-\x2F]*[\x40-\x7E]/', '', $value) ?? $value;
        $withoutOsc = preg_replace('/\x1b\][^\x07\x1b]*(?:\x07|\x1b\\\\)/', '', $withoutAnsi) ?? $withoutAnsi;

        return preg_replace('/[^\P{C}\n\r\t]/u', '', $withoutOsc) ?? $withoutOsc;
    }
}
