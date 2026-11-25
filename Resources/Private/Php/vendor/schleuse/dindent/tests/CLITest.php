<?php

declare(strict_types=1);


class CLITest extends \PHPUnit\Framework\TestCase
{
    public function testMissingInputParameter(): void
    {
        $this->assertSame('Missing "input" parameter.', static::exec('--foo'));
    }
    public function testInputFileDoesNotExist(): void
    {
        $this->assertSame('"input" file does not exist.', static::exec('--input="./bar.html"'));
    }

    public function testIndentOutput(): void
    {
        $this->assertSame('<div></div>', static::exec('--input=' . escapeshellarg(__DIR__ . '/sample/input/0-empty-block.html')));
    }

    public static function exec(string $arguments): string|false|null
    {
        return shell_exec('php ' . escapeshellarg(__DIR__ . '/../bin/dindent.php') . ' ' . $arguments);
    }

    /** @return array<int, array<int, string>> */
    public function indentProvider(): array
    {
        return array_map(function ($e) {
            return [pathinfo($e, \PATHINFO_FILENAME)];
        }, glob(__DIR__ . '/input/*.html') ?: []);
    }
}
