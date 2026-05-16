<?php

namespace Sado729\ProjectVersion\Support;

use Symfony\Component\Process\Process;

class Git
{
    public function __construct(private ?string $workingDirectory = null)
    {
    }

    /**
     * Run a git subcommand and return stdout. Returns an empty string on
     * non-zero exit codes — callers that care can inspect the result.
     *
     * @param  array<int, string>  $arguments
     */
    public function run(array $arguments): string
    {
        $process = new Process(
            array_merge(['git'], $arguments),
            $this->workingDirectory ?? (function_exists('base_path') ? base_path() : (getcwd() ?: null))
        );

        try {
            $process->run();
        } catch (\Throwable) {
            return '';
        }

        return $process->isSuccessful() ? $process->getOutput() : '';
    }

    public function mustRun(array $arguments): string
    {
        $process = new Process(
            array_merge(['git'], $arguments),
            $this->workingDirectory ?? (function_exists('base_path') ? base_path() : (getcwd() ?: null))
        );
        $process->mustRun();

        return $process->getOutput();
    }
}
