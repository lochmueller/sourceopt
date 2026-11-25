<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;

class IndenterTest extends \PHPUnit\Framework\TestCase
{
    public function testInvalidSetupOption(): void
    {
        $this->expectException(\Gajus\Dindent\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unrecognized option.');
        new \Gajus\Dindent\Indenter(['foo' => 'bar']);
    }

    public function testIndentCustomCharacter(): void
    {
        $indenter = new \Gajus\Dindent\Indenter(['indentation_character' => 'X']);

        $indented = $indenter->indent('<p><p></p></p>');

        $expected_output = '<p>X<p></p></p>';

        $this->assertSame($expected_output, str_replace("\n", '', $indented));
    }

    public function testOneLineNoIndent(): void
    {
        $indenter = new \Gajus\Dindent\Indenter(['indentation_character' => null]);

        $indented = $indenter->indent("\n<p>\n  <p></p>\n</p>\n\n");

        $expected_output = '<p><p></p></p>';

        $this->assertSame($expected_output, $indented);
    }

    #[DataProvider('indentProvider')]
    public function testIndent(string $name): void
    {
        $indenter = new \Gajus\Dindent\Indenter();

        $input = file_get_contents(__DIR__ . '/sample/input/' . $name . '.html');
        $expected_output = file_get_contents(__DIR__ . '/sample/output/' . $name . '.html');

        assert($input !== false);
        assert($expected_output !== false);

        $this->assertSame($expected_output, $indenter->indent($input));
    }

    /** @return array<int, array<int, string>> */
    public static function indentProvider(): array
    {
        return array_map(function ($e) {
            return [pathinfo($e, \PATHINFO_FILENAME)];
        }, glob(__DIR__ . '/sample/input/*.html') ?: []);
    }
}
