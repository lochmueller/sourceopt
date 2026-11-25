<?php

declare(strict_types=1);

namespace Gajus\Dindent;

/**
 * @link https://github.com/gajus/dindent for the canonical source repository
 * @license https://github.com/gajus/dindent/blob/master/LICENSE BSD 3-Clause
 *
 * @phpstan-type LogEntry array{rule: string, pattern: string, subject: string, match: string}
 * @phpstan-type Options array{indentation_character: string|null, logging: bool}
 * @phpstan-type FormatReplacement array{elm: string, str: string}
 * @phpstan-type SourceReplacement array{str: string, lf: bool|string}
 */
class Indenter
{
    /**
     * @var LogEntry[]
     */
    private array $log = [];

    /**
     * @var Options
     */
    private array $options = [
        'indentation_character' => '    ',
        'logging' => false,
    ];

    // https://developer.mozilla.org/en-US/docs/Glossary/Void_element
    /** @var list<string> */
    private array $void_elements = [
        'area','base','br','col','embed','hr','img',
        'input','link','meta','source','track','wbr',
    ];
    // https://developer.mozilla.org/en-US/docs/Web/HTML/Element#inline_text_semantics
    /** @var array<int, string> */
    private array $inline_elements = [
        'a', 'abbr', 'b', 'bdi', 'bdo', 'big', 'cite',
        'code', 'data', 'dfn', 'em', 'i', 'kbd', 'mark',
        'q', 's', 'samp', 'small', 'span', 'strong',
        'sub', 'sup', 'time', 'u', 'var', 'acronym','tt',
    ];

    /** @var list<FormatReplacement> */
    private array $temporary_replacements_format = [];
    /** @var list<SourceReplacement> */
    private array $temporary_replacements_source = [];
    /** @var list<string> */
    private array $temporary_replacements_inline = [];

    /**
     * @param array{indentation_character?: string|null, logging?: bool} $options
     */
    public function __construct(array $options = [])
    {
        foreach ($options as $name => $value) {
            if (!array_key_exists($name, $this->options)) {
                throw new Exception\InvalidArgumentException('Unrecognized option.');
            }

            // @phpstan-ignore-next-line
            $this->options[$name] = $value;
        }
    }

    /**
     * @param string $element_name Element name, e.g. "b".
     * @param ElementType $type
     */
    public function setElementType(string $element_name, ElementType $type): void
    {
        if ($type === ElementType::Block) {
            $this->inline_elements = array_diff($this->inline_elements, [$element_name]);
        } elseif ($type === ElementType::Inline) {
            $this->inline_elements[] = $element_name;
        } else {
            throw new Exception\InvalidArgumentException('Unrecognized element type.');
        }
        $this->inline_elements = array_unique($this->inline_elements);
    }

    /**
     * @param string $input HTML input.
     * @return string Indented HTML.
     */
    public function indent(string $input): string
    {
        $this->log = [];

        // Remove trailing spaces
        $input = preg_replace('/\h+$/m', '', $input);

        $count = 0; // keep!
        // Dindent does not touch `<pre|textarea>` body. Instead, it temporary removes it from the code, indents the input, and restores the body.
        $input = preg_replace_callback(
            '/(?<elm><(pre|textarea)[^>]*>)(?<str>[\s\S]*?)(?=<\/\2>)/i',
            function ($match) use (&$count): string {
                if (empty($match['str'])) {
                    return $match[0];
                }
                $this->temporary_replacements_format[] = $match;
                return $match['elm'] . 'ᐂᐂᐂ' . $count++ . 'ᐂᐂᐂ';
            },
            $input,
        );

        // Remove empty lines
        $input = preg_replace('/^\n+/m', '', $input);

        $count = 0; // keep!
        // Dindent does not touch `<!-- -->` body. Instead, it temporary removes it from the code, indents the input, and restores the body.
        $input = preg_replace_callback(
            '/(?<=<!--)\s*(?<str>[\s\S]+?)\s*(?=-->)/',
            function ($match) use (&$count): string {
                if (empty($match['str'])) {
                    return $match[0];
                }
                if (str_contains($match['str'], "\n")) {
                    $match['lf'] = true;// HACK
                    $match['str'] = "\n" . preg_replace('/^\s+|\s+$/m', '', $match['str']);
                } else {
                    $match['lf'] = false;// HACK
                    $match['str'] = ' ' . $match['str'] . ' ';
                }

                $this->temporary_replacements_source[] = $match;
                return                 'ᐄᐄᐄ' . $count++ . 'ᐄᐄᐄ';
            },
            $input,
        );

        // Dindent does not indent `<script|style>` body. Instead, it temporary removes it from the code, indents the input, and restores the body.
        $input = preg_replace_callback(
            '/(?<elm><(script|style)[^>]*>)(?<str>[\s\S]*?)(?<lf>\n?)\s*(?=<\/\2>)/i',
            function ($match) use (&$count): string {
                if (empty($match['str'])) {
                    return $match[0];
                }
                $this->temporary_replacements_source[] = $match;
                return $match['elm'] . 'ᐄᐄᐄ' . $count++ . 'ᐄᐄᐄ';
            },
            $input,
        );

        // Shrink global whitespace
        $input = preg_replace('/\s+/', ' ', $input);
        // Remove leading whitespace
        $input = preg_replace('/^ /m', '', $input);

        $count = 0; // keep!
        // Temporary remove inline elements
        $input = preg_replace_callback(
            '/\s*(?<elm><(' . implode('|', $this->inline_elements) . ')[^>]*>)\s*(?<str>[^<]*?)\s*(?<clt><\/\2>)\s*/i',
            function ($match) use (&$count): string {
                if (empty($match['str'])) {
                    return $match[0];
                }
                $this->temporary_replacements_inline[] = sprintf(' %s%s%s ', $match['elm'], $match['str'], $match['clt']);
                return 'ᐃᐃᐃ' . $count++ . 'ᐃᐃᐃ';
            },
            $input,
        );

        // Discard useless whitespace
        $input = preg_replace('/(<[^>]+>) (?=<)/', '$1', ltrim($input));

        $output   = '';
        $subject  = null;
        $indLen   = 0;
        $indent   = '';
        $patterns = [];

        // NO line-breake mode!
        if (null === $this->options['indentation_character']) {
            $this->options['logging'] = false;// HACK

            $output = str_replace("\n", '', $input);
        } else {
            $subject  = preg_replace_callback(
                '/<!DOCTYPE[^>]+>/i',
                function ($match) use (&$output): string {
                    $output = $match[0] . "\n";
                    return '';
                },
                $input,
            );

            $indLen   = -1 * strlen($this->options['indentation_character']);
            $patterns = [
                // comment
                '/^<!--[\s\S]*?-->/' => MatchType::IndentKeep,
                // standart element
                '/^<([a-z][\w\-]*)(?: [^<]*)?>[^<]*<\/\1>/' => MatchType::IndentKeep,
                // implied closing
                '/^<(?:' . implode('|', $this->void_elements) . ')[^>]*>/' => MatchType::IndentKeep,
                // self-closing
                '/^<[^>]+\/>/' => MatchType::IndentKeep,

                // closing tag
                '/^<\/[^>]+>/' => MatchType::IndentDecrease,
                // opening tag
                '/^<[^>]+>/' => MatchType::IndentIncrease,
                // text node
                '/^[^<]+/' => MatchType::IndentKeep,
            ];
        }
        while ($subject) {
            foreach ($patterns as $pattern => $rule) {
                if (preg_match($pattern, $subject, $matches)) {// TODO; check speed `PREG_OFFSET_CAPTURE` vs `mb_strlen()`
                    if ($this->options['logging']) {
                        $this->log[] = [
                            'rule'    => $rule->asString(),
                            'pattern' => $pattern,
                            'match'   => $matches[0],
                            'subject' => $subject,
                        ];
                    }

                    $subject = mb_substr($subject, mb_strlen($matches[0]));

                    switch ($rule) {
                        case MatchType::IndentIncrease:
                            $output .= $indent . $matches[0] . "\n";
                            $indent .= $this->options['indentation_character'];
                            break 2;

                        case MatchType::IndentDecrease:
                            $indent = substr($indent, 0, $indLen);

                            // no break
                        case MatchType::IndentKeep:
                            $output .= $indent . $matches[0] . "\n";
                            break 2;

                        default:
                            throw new Exception\RuntimeException("MatchType?:{$rule}");
                    }
                }
            }
        }

        if ($this->options['logging']) {
            $interpreted_input = '';
            foreach ($this->log as $e) {
                $interpreted_input .= $e['match'];
            }

            if ($interpreted_input !== $input) {
                if (!empty($this->log)) {
                    var_dump($this->log);
                }
                throw new Exception\RuntimeException("\n{$interpreted_input}\n!==\n{$input}\n");
            }
        }

        // Restore inline elements
        foreach ($this->temporary_replacements_inline as $i => $original) {
            $output = str_replace('ᐃᐃᐃ' . $i . 'ᐃᐃᐃ', $original, $output);
        }

        // Remove empty space inside & between tags
        $output = preg_replace('/(<[^>]+>) (?=<)/', '$1', $output);

        // Restore `<pre|textarea>`.
        foreach ($this->temporary_replacements_format as $i => $original) {
            $output = preg_replace('/( *)(<[^>]+>?)?ᐂᐂᐂ' . $i . 'ᐂᐂᐂ/', '$1$2' . $original['str'], $output);
        }

        // Restore `<script|style>` & `<!-- -->`
        foreach (array_reverse($this->temporary_replacements_source, true) as $i => $original) {
            $output = preg_replace('/( +)?(<[^>]+>?)?ᐄᐄᐄ' . $i . 'ᐄᐄᐄ/m', preg_replace('/^/m', '\\$1', '$2' . $original['str']) . (!empty($original['lf']) ? "\n$1" : ''), $output);
        }

        return rtrim($output);
    }

    /**
     * Debugging utility. Get log for the last indent operation.
     *
     * @return LogEntry[]
     */
    public function getLog(): array
    {
        return $this->log;
    }
}
