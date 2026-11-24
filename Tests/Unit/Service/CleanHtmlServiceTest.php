<?php

declare(strict_types=1);

namespace HTML\Sourceopt\Tests\Unit\Service;

use HTML\Sourceopt\Service\CleanHtmlService;
use HTML\Sourceopt\Tests\Unit\AbstractUnitTest;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

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

    /**
     * Invalid UTF-8 must not break the page, the markup is delivered unchanged.
     *
     * @see https://github.com/lochmueller/sourceopt/issues/162
     */
    public function testInvalidUtf8IsDeliveredUnchangedAndLogged(): void
    {
        $logger = new class extends AbstractLogger {
            public array $records = [];

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->records[] = [$level, (string) $message, $context];
            }
        };
        $cleanService = new CleanHtmlService();
        $cleanService->setLogger($logger);
        $html = "<head>\r\n<meta name=\"a\" />\xC3\x28</head>";

        self::assertSame($html, $cleanService->clean($html, ['formatHtml' => 2]));
        self::assertCount(1, $logger->records);
        self::assertSame(LogLevel::WARNING, $logger->records[0][0]);
        self::assertStringContainsString("\u{FFFD}", $logger->records[0][2]['snippet']);
    }

    /**
     * A failing preg_replace() (e.g. pcre.backtrack_limit on huge pages)
     * keeps the markup instead of throwing a TypeError.
     *
     * @see https://github.com/lochmueller/sourceopt/issues/162
     */
    public function testPcreErrorsKeepTheMarkup(): void
    {
        $backtrackLimit = ini_get('pcre.backtrack_limit');
        $jit = ini_get('pcre.jit');
        ini_set('pcre.backtrack_limit', '1');
        ini_set('pcre.jit', '0');

        try {
            $cleanService = new CleanHtmlService();
            $html = '<head><meta name="generator" content="TYPO3 CMS" /><meta name="viewport" content="width=device-width" /></head>';

            self::assertSame($html, $cleanService->clean($html, ['removeGenerator' => 1]));
        } finally {
            ini_set('pcre.backtrack_limit', (string) $backtrackLimit);
            ini_set('pcre.jit', (string) $jit);
        }
    }

    /**
     * Inline SVGs with huge attributes (long path data, embedded base64
     * images) exhausted the PCRE backtrack limit inside dindent. They are
     * formatted exactly like the same SVG with short attributes.
     *
     * @see https://github.com/lochmueller/sourceopt/issues/176
     */
    #[DataProvider('formatTypeWithLineBreaksProvider')]
    public function testFormatHtmlWithHugeInlineSvg(int $formatType): void
    {
        $logger = new class extends AbstractLogger {
            public array $records = [];

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->records[] = [$level, (string) $message, $context];
            }
        };
        $svg = static fn (string $path, string $image): string => '<div>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
  <defs>
    <style>
      .st0 {
        fill: #035aa9;
      }
    </style>
  </defs>
  <!-- Generator: Adobe Illustrator -->
  <path class="st0" d="' . $path . '"/>
  <image href=\'data:image/png;base64,' . $image . '\'/>
</svg>
<script>var a = 1;</script>
</div>';
        $config = ['formatHtml' => $formatType, 'formatHtml.' => ['tabSize' => 2]];

        $cleanService = new CleanHtmlService();
        $cleanService->setLogger($logger);
        $path = str_repeat('M10.5,20.3c1.2-3.4,5.6-7.8,9.1-2.2 ', 15000);
        $image = str_repeat('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk', 10000);

        $expected = str_replace(
            ['PATH', 'IMAGE'],
            [$path, $image],
            (new CleanHtmlService())->clean($svg('PATH', 'IMAGE'), $config)
        );

        self::assertSame($expected, $cleanService->clean($svg($path, $image), $config));
        self::assertSame([], $logger->records);
    }

    public static function formatTypeWithLineBreaksProvider(): array
    {
        return [
            'one line' => [1],
            'indented' => [2],
        ];
    }
}
