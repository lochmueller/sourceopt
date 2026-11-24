<?php

declare(strict_types=1);

namespace HTML\Sourceopt\Service;

use Gajus\Dindent\Indenter;
use HTML\Sourceopt\Manipulation\ManipulationInterface;
use HTML\Sourceopt\Manipulation\RemoveComments;
use HTML\Sourceopt\Manipulation\RemoveGenerator;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service: Clean parsed HTML functionality
 * Based on the extension 'sourceopt'.
 */
class CleanHtmlService implements SingletonInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Attribute values from this length on are hidden from dindent.
     */
    private const LONG_ATTRIBUTE_LENGTH = 1024;

    /**
     * Private use character, marks where an inline element touches its neighbours.
     */
    private const INLINE_BOUNDARY_MARKER = "\u{E000}";

    /**
     * Inline elements as known by dindent.
     */
    private const INLINE_ELEMENTS = 'a|abbr|acronym|b|bdi|bdo|big|cite|code|data|dfn|em|i|kbd|mark|q|s|samp|small|span|strong|sub|sup|time|tt|u|var';

    /**
     * Enable Debug comment in footer.
     */
    protected bool $debugComment = false;

    /**
     * Format type: 0 disables formatting, 1 removes all line breaks, anything
     * above 1 adds line breaks and indentation.
     */
    protected int $formatType = 0;

    /**
     * Tab character.
     */
    protected string $tab = "\t";

    /**
     * Newline character.
     */
    protected string $newline = "\n";

    /**
     * Configured extra header comment.
     */
    protected string $headerComment = '';

    /**
     * Empty space char.
     */
    protected string $emptySpaceChar = ' ';

    /**
     * Set variables based on given config.
     */
    public function setVariables(array $config): void
    {
        if (isset($config['headerComment']) && !empty($config['headerComment'])) {
            $this->headerComment = $config['headerComment'];
        }

        if (isset($config['formatHtml']) && is_numeric($config['formatHtml'])) {
            $this->formatType = (int) $config['formatHtml'];
        }

        if (isset($config['formatHtml.']['tabSize']) && is_numeric($config['formatHtml.']['tabSize'])) {
            $this->tab = str_pad('', (int) $config['formatHtml.']['tabSize'], ' ');
        }

        if (isset($config['formatHtml.']['debugComment'])) {
            $this->debugComment = (bool) $config['formatHtml.']['debugComment'];
        }

        if (isset($config['dropEmptySpaceChar']) && (bool) $config['dropEmptySpaceChar']) {
            $this->emptySpaceChar = '';
        }
    }

    /**
     * Extract the surroundings of the first invalid UTF-8 character.
     */
    private function getInvalidUTF8Snippet(string $html, int $excerpt = 42): string
    {
        $substituteCharacter = mb_substitute_character();
        mb_substitute_character(0xFFFD);
        $html = mb_convert_encoding($html, 'UTF-8', 'UTF-8');
        mb_substitute_character($substituteCharacter);

        $html = (string) preg_replace('/\s+/', ' ', $html);
        $pos = (int) mb_strpos($html, mb_chr(0xFFFD, 'UTF-8'), 0, 'UTF-8');

        return mb_substr($html, max(0, $pos - $excerpt), $excerpt << 1, 'UTF-8');
    }

    /**
     * preg_replace() returns null on PCRE errors (backtrack limit, JIT stack, ...).
     * Keep the unchanged HTML in that case instead of breaking the whole page.
     */
    private function replace(string $pattern, string $replacement, string $html, int $limit = -1): string
    {
        $result = preg_replace($pattern, $replacement, $html, $limit);
        if (null === $result) {
            $this->logger?->warning('EXT:sourceopt skipped the pattern {pattern}: {error}', [
                'pattern' => $pattern,
                'error' => preg_last_error_msg(),
            ]);

            return $html;
        }

        return $result;
    }

    /**
     * Huge attribute values (SVG path data, base64 images, ...) exhaust the
     * PCRE backtrack limit in dindent, which scans every tag up to its ">".
     * Swap them with short placeholders, the caller restores them via strtr().
     *
     * @param array<string, string> $attributes placeholder => original value
     *
     * @see https://github.com/lochmueller/sourceopt/issues/176
     */
    private function protectLongAttributeValues(string $html, array &$attributes): string
    {
        $result = preg_replace_callback(
            '/(?<==)(?:"[^"<]{' . self::LONG_ATTRIBUTE_LENGTH . ',}+"|\'[^\'<]{' . self::LONG_ATTRIBUTE_LENGTH . ',}+\')/',
            static function (array $match) use (&$attributes): string {
                $placeholder = 'ᐅᐅᐅ' . \count($attributes) . 'ᐅᐅᐅ';
                $attributes[$placeholder] = $match[0];

                return $placeholder;
            },
            $html
        );
        if (null === $result) {
            $attributes = [];

            return $html;
        }

        return $result;
    }

    /**
     * Dindent pads every inline element with spaces on both sides, which
     * turns "amet<strong>!</strong>" into "amet <strong>!</strong>" and
     * "</a>, text" into "</a> , text". Mark every boundary where an inline
     * element touches text or another inline element, so the padding can be
     * removed again afterwards.
     *
     * @see https://github.com/lochmueller/sourceopt/issues/172
     */
    private function protectInlineBoundaries(string $html): string
    {
        if (str_contains($html, self::INLINE_BOUNDARY_MARKER)) {
            return $html;
        }

        $inline = '(?:' . self::INLINE_ELEMENTS . ')';

        // text directly before an opening inline tag
        $html = $this->replace('/(?<=[^\s>])(?=<' . $inline . '[\s>])/i', self::INLINE_BOUNDARY_MARKER, $html);

        // text or another inline element directly after a closing inline tag
        return $this->replace('/<\/' . $inline . '>\K(?=[^\s<]|<' . $inline . '[\s>])/i', self::INLINE_BOUNDARY_MARKER, $html);
    }

    /**
     * The marker never touches whitespace in the input, so any whitespace
     * around it was added by dindent and is dropped together with it.
     */
    private function restoreInlineBoundaries(string $html): string
    {
        if (!str_contains($html, self::INLINE_BOUNDARY_MARKER)) {
            return $html;
        }

        $result = preg_replace('/\s*' . self::INLINE_BOUNDARY_MARKER . '\s*/u', '', $html);
        if (null === $result) {
            throw new \RuntimeException('Restoring inline boundaries failed: ' . preg_last_error_msg());
        }

        return $result;
    }

    /**
     * Clean given HTML with formatter.
     *
     * Invalid UTF-8 or failing regular expressions never break the page,
     * the affected step is skipped and a warning is logged instead.
     *
     * @param string $doctype TypoScript "config.doctype" of the current page
     */
    public function clean(string $html, array $config = [], string $doctype = ''): string
    {
        if (!mb_check_encoding($html, 'UTF-8')) {
            $this->logger?->warning('EXT:sourceopt skipped the cleanup, invalid UTF-8 detected @ {snippet}', [
                'snippet' => $this->getInvalidUTF8Snippet($html),
            ]);

            return $html;
        }

        if (!empty($config)) {
            $this->setVariables($config);
        }

        // convert line-breaks to UNIX
        $html = $this->replace("(\r\n|\r)", $this->newline, $html);

        $manipulations = [];

        if (isset($config['removeGenerator']) && (bool) $config['removeGenerator']) {
            $manipulations['removeGenerator'] = GeneralUtility::makeInstance(RemoveGenerator::class);
        }

        if (isset($config['removeComments']) && (bool) $config['removeComments']) {
            $manipulations['removeComments'] = GeneralUtility::makeInstance(RemoveComments::class);
        }

        foreach ($manipulations as $key => $manipulation) {
            /** @var ManipulationInterface $manipulation */
            $configuration = isset($config[$key . '.']) && \is_array($config[$key . '.']) ? $config[$key . '.'] : [];
            $html = $manipulation->manipulate($html, $configuration);
        }

        // include configured header comment in HTML content block
        if (!empty($this->headerComment)) {
            $html = $this->replace('/^(-->)$/m', "\n\t" . $this->headerComment . "\n$1", $html, 1);
        }

        // cleanup HTML5 self-closing elements
        if ('x' !== substr($doctype, 0, 1)) {
            $html = $this->replace(
                '/<((?:area|base|br|col|embed|hr|img|input|link|meta|param|source|track|wbr)\s[^>]+?)\s*\\\?\/>/',
                '<$1>',
                $html
            );
        }

        if ($this->formatType) {
            // 1 puts the whole document on one line, anything above indents it
            $indenter = new Indenter([
                'indentation_character' => 1 === $this->formatType ? null : $this->tab,
            ]);
            try {
                $attributes = [];
                $formatted = $this->protectLongAttributeValues($html, $attributes);
                $formatted = $this->protectInlineBoundaries($formatted);
                $formatted = strtr($indenter->indent($formatted), $attributes);
                $html = $this->restoreInlineBoundaries($formatted);
            } catch (\Throwable $exception) {
                $this->logger?->warning('EXT:sourceopt skipped formatHtml: {message}', [
                    'message' => $exception->getMessage(),
                    'exception' => $exception,
                ]);
            }
        }

        // recover line-breaks
        if (Environment::isWindows()) {
            $html = str_replace($this->newline, "\r\n", $html);
        }

        return $html;
    }
}
