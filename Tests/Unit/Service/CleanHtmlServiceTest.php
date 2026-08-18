<?php

declare(strict_types=1);

namespace HTML\Sourceopt\Tests\Unit\Service;

use HTML\Sourceopt\Service\CleanHtmlService;
use HTML\Sourceopt\Tests\Unit\AbstractUnitTest;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @internal
 */
#[CoversNothing]
class CleanHtmlServiceTest extends AbstractUnitTest
{
    public function testFormatHtml(): void
    {
        $cleanService = new CleanHtmlService();
        $config = [
            'enabled' => true,
            'removeComments' => true,
            'formatHtml' => 4,
            'formatHtml.' => [
                'tabSize' => 2,
            ],
        ];

        $svg =
'<svg>
  <path/>
  <path/>
  <path>
    <path/>
    <path>
      <path>
        <path></path>
      </path>
    </path>
  </path>
</svg>';

        self::assertSame($svg, $cleanService->clean($svg, $config));
    }

    /**
     * formatHtml knows three levels: off, everything on one line, and line
     * breaks with indentation for anything above 1.
     */
    #[DataProvider('formatTypeProvider')]
    public function testFormatHtmlLevels(int $formatType, string $expected): void
    {
        $cleanService = new CleanHtmlService();
        $html = "<div>\n\t<ul>\n\t\t<li>a</li>\n\t</ul>\n</div>";

        self::assertSame($expected, $cleanService->clean($html, ['formatHtml' => $formatType]));
    }

    public static function formatTypeProvider(): array
    {
        $indented = "<div>\n\t<ul>\n\t\t<li>a</li>\n\t</ul>\n</div>";

        return [
            '0 leaves the markup alone' => [0, $indented],
            '1 removes every line break' => [1, '<div><ul><li>a</li></ul></div>'],
            '2 indents' => [2, $indented],
            '4 still indents, as it did before' => [4, $indented],
        ];
    }

    /**
     * The doctype used to come from $GLOBALS['TSFE'], it is now passed in.
     */
    #[DataProvider('doctypeProvider')]
    public function testSelfClosingTagsDependOnDoctype(string $doctype, string $expected): void
    {
        $cleanService = new CleanHtmlService();
        $html = '<head><meta name="viewport" content="width=device-width" /></head>';

        self::assertSame($expected, $cleanService->clean($html, ['formatHtml' => 0], $doctype));
    }

    public static function doctypeProvider(): array
    {
        return [
            'html5 drops the self-closing slash' => [
                '',
                '<head><meta name="viewport" content="width=device-width"></head>',
            ],
            'xhtml keeps it' => [
                'xhtml',
                '<head><meta name="viewport" content="width=device-width" /></head>',
            ],
        ];
    }

    public function testInvalidUtf8IsRejected(): void
    {
        $cleanService = new CleanHtmlService();

        $this->expectException(\Exception::class);
        $cleanService->clean("<head>\xC3\x28</head>");
    }
}
