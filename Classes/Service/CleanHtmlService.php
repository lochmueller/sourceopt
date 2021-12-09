<?php

declare(strict_types=1);

namespace HTML\Sourceopt\Service;

use HTML\Sourceopt\Manipulation\ManipulationInterface;
use HTML\Sourceopt\Manipulation\RemoveComments;
use HTML\Sourceopt\Manipulation\RemoveGenerator;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service: Clean parsed HTML functionality
 * Based on the extension 'sourceopt'.
 */
class CleanHtmlService implements SingletonInterface
{
    /**
     * Enable Debug comment in footer.
     *
     * @var bool
     */
    protected $debugComment = false;

    /**
     * Format Type.
     *
     * @var int
     */
    protected $formatType = 0;

    /**
     * Tab character.
     *
     * @var string
     */
    protected $tab = "\t";

    /**
     * Newline character.
     *
     * @var string
     */
    protected $newline = "\n";

    /**
     * Configured extra header comment.
     *
     * @var string
     */
    protected $headerComment = '';

    /**
     * Empty space char.
     *
     * @var string
     */
    protected $emptySpaceChar = ' ';

    /**
     * Set variables based on given config.
     */
    public function setVariables(array $config): void
    {
        if (!empty($config)) {
            if ($config['formatHtml'] && is_numeric($config['formatHtml'])) {
                $this->formatType = (int) $config['formatHtml'];
            }

            if ($config['formatHtml.']['tabSize'] && is_numeric($config['formatHtml.']['tabSize'])) {
                $this->tab = str_pad('', (int) $config['formatHtml.']['tabSize'], ' ');
            }

            if (isset($config['formatHtml.']['debugComment'])) {
                $this->debugComment = (bool) $config['formatHtml.']['debugComment'];
            }

            if (isset($config['headerComment'])) {
                $this->headerComment = $config['headerComment'];
            }

            if (isset($config['dropEmptySpaceChar']) && (bool) $config['dropEmptySpaceChar']) {
                $this->emptySpaceChar = '';
            }
        }
    }

    /**
     * Clean given HTML with formatter.
     *
     * @param string $html
     * @param array  $config
     *
     * @return string
     */
    public function clean($html, $config = [])
    {
        if (!empty($config)) {
            $this->setVariables($config);
        }
        // convert line-breaks to UNIX
        $this->convNlOs($html);

        $manipulations = [];

        if (isset($config['removeGenerator']) && (bool) $config['removeGenerator']) {
            $manipulations['removeGenerator'] = GeneralUtility::makeInstance(RemoveGenerator::class);
        }

        if (isset($config['removeComments']) && (bool) $config['removeComments']) {
            $manipulations['removeComments'] = GeneralUtility::makeInstance(RemoveComments::class);
        }

        if (!empty($this->headerComment)) {
            $this->includeHeaderComment($html);
        }

        foreach ($manipulations as $key => $manipulation) {
            /** @var ManipulationInterface $manipulation */
            $configuration = isset($config[$key.'.']) && \is_array($config[$key.'.']) ? $config[$key.'.'] : [];
            $html = $manipulation->manipulate($html, $configuration);
        }

        // cleanup HTML5 self-closing elements
        if (!isset($GLOBALS['TSFE']->config['config']['doctype'])
            || 'x' !== substr($GLOBALS['TSFE']->config['config']['doctype'], 0, 1)) {
            $html = preg_replace(
                '/<((?:area|base|br|col|embed|hr|img|input|link|meta|param|source|track|wbr)\s[^>]+?)\s?\/>/',
                '<$1>',
                $html
            );
        }

        if ($this->formatType > 0) {
            $html = $this->formatHtml($html);
        }
        // remove white space after line ending
        $this->rTrimLines($html);

        // recover line-breaks
        if (Environment::isWindows()) {
            $html = str_replace($this->newline, "\r\n", $html);
        }

        return $html;
    }

    /**
     * Formats the (X)HTML code:
     *  - taps according to the hirarchy of the tags
     *  - removes empty spaces between tags
     *  - removes linebreaks within tags (spares where necessary: pre, textarea, comments, ..)
     *  choose from five options:
     *    0 => off
     *    1 => no line break at all  (code in one line)
     *    2 => minimalistic line breaks (structure defining box-elements)
     *    3 => aesthetic line breaks (important box-elements)
     *    4 => logic line breaks (all box-elements)
     *    5 => max line breaks (all elements).
     *
     * @param string $html
     *
     * @return string
     */
    protected function formatHtml($html)
    {
        // Save original formated comments, pre, textarea, styles and java-scripts & replace them with markers
        preg_match_all(
            '/(?s)((<!--.*?-->)|(<[ \n\r]*pre[^>]*>.*?<[ \n\r]*\/pre[^>]*>)|(<[ \n\r]*textarea[^>]*>.*?<[ \n\r]*\/textarea[^>]*>)|(<[ \n\r]*style[^>]*>.*?<[ \n\r]*\/style[^>]*>)|(<[ \n\r]*script[^>]*>.*?<[ \n\r]*\/script[^>]*>))/im',
            $html,
            $matches
        );
        $noFormat = $matches[0]; // do not format these block elements
        for ($i = 0; $i < \count($noFormat); ++$i) {
            $html = str_replace($noFormat[$i], "\n<!-- ELEMENT {$i} -->", $html);
        }

        // define box elements for formatting
        $trueBoxElements = 'address|blockquote|center|dir|div|dl|fieldset|form|h1|h2|h3|h4|h5|h6|hr|isindex|menu|noframes|noscript|ol|p|pre|table|ul|article|aside|details|figcaption|figure|footer|header|hgroup|menu|nav|section';
        $functionalBoxElements = 'dd|dt|frameset|li|tbody|td|tfoot|th|thead|tr|colgroup';
        $usableBoxElements = 'applet|button|del|iframe|ins|map|object|script';
        $imagineBoxElements = 'html|body|head|meta|title|link|script|base|!--';
        $allBoxLikeElements = '(?>'.$trueBoxElements.'|'.$functionalBoxElements.'|'.$usableBoxElements.'|'.$imagineBoxElements.')';
        $esteticBoxLikeElements = '(?>html|head|body|meta name|title|div|table|h1|h2|h3|h4|h5|h6|p|form|pre|center|!--)';
        $structureBoxLikeElements = '(?>html|head|body|div|!--)';

        // split html into it's elements
        $htmlArrayTemp = preg_split(
            '/(<(?:[^<>]+(?:"[^"]*"|\'[^\']*\')?)+>)/',
            $html,
            -1,
            \PREG_SPLIT_DELIM_CAPTURE | \PREG_SPLIT_NO_EMPTY
        );

        if (false === $htmlArrayTemp) {
            // Restore saved comments, styles and java-scripts
            for ($i = 0; $i < \count($noFormat); ++$i) {
                $html = str_replace("<!-- ELEMENT {$i} -->", $noFormat[$i], $html);
            }

            return $html;
        }
        // remove empty lines
        $htmlArray = [''];
        $index = 1;
        for ($x = 0; $x < \count($htmlArrayTemp); ++$x) {
            $text = trim($htmlArrayTemp[$x]);
            $htmlArray[$index] = '' !== $text ? $htmlArrayTemp[$x] : $this->emptySpaceChar;
            ++$index;
        }

        // rebuild html
        $html = '';
        $tabs = 0;
        for ($x = 0; $x < \count($htmlArray); ++$x) {
            $htmlArrayBefore = $htmlArray[$x - 1] ?? '';
            $htmlArrayCurrent = $htmlArray[$x] ?? '';

            // check if the element should stand in a new line
            $newline = false;
            if ('<?xml' == substr($htmlArrayBefore, 0, 5)) {
                $newline = true;
            } elseif (2 == $this->formatType && ( // minimalistic line break
                    // this element has a line break before itself
                    preg_match(
                        '/<'.$structureBoxLikeElements.'(.*)>/Usi',
                        $htmlArrayCurrent
                    ) || preg_match(
                        '/<'.$structureBoxLikeElements.'(.*) \/>/Usi',
                        $htmlArrayCurrent
                    ) // one element before is a element that has a line break after
                    || preg_match(
                        '/<\/'.$structureBoxLikeElements.'(.*)>/Usi',
                        $htmlArrayBefore
                    ) || '<!--' == substr(
                        $htmlArrayBefore,
                        0,
                        4
                    ) || preg_match('/<'.$structureBoxLikeElements.'(.*) \/>/Usi', $htmlArrayBefore))
            ) {
                $newline = true;
            } elseif (3 == $this->formatType && ( // aestetic line break
                    // this element has a line break before itself
                    preg_match(
                        '/<'.$esteticBoxLikeElements.'(.*)>/Usi',
                        $htmlArrayCurrent
                    ) || preg_match(
                        '/<'.$esteticBoxLikeElements.'(.*) \/>/Usi',
                        $htmlArrayCurrent
                    ) // one element before is a element that has a line break after
                    || preg_match('/<\/'.$esteticBoxLikeElements.'(.*)>/Usi', $htmlArrayBefore) || '<!--' == substr(
                        $htmlArrayBefore,
                        0,
                        4
                    ) || preg_match('/<'.$esteticBoxLikeElements.'(.*) \/>/Usi', $htmlArrayBefore))
            ) {
                $newline = true;
            } elseif ($this->formatType >= 4 && ( // logical line break
                    // this element has a line break before itself
                    preg_match(
                        '/<'.$allBoxLikeElements.'(.*)>/Usi',
                        $htmlArrayCurrent
                    ) || preg_match(
                        '/<'.$allBoxLikeElements.'(.*) \/>/Usi',
                        $htmlArrayCurrent
                    ) // one element before is a element that has a line break after
                    || preg_match('/<\/'.$allBoxLikeElements.'(.*)>/Usi', $htmlArrayBefore) || '<!--' == substr(
                        $htmlArrayBefore,
                        0,
                        4
                    ) || preg_match('/<'.$allBoxLikeElements.'(.*) \/>/Usi', $htmlArrayBefore))
            ) {
                $newline = true;
            }

            // count down a tab
            if ('</' == substr($htmlArrayCurrent, 0, 2)) {
                --$tabs;
            }

            // add tabs and line breaks in front of the current tag
            if ($newline) {
                $html .= $this->newline;
                for ($y = 0; $y < $tabs; ++$y) {
                    $html .= $this->tab;
                }
            }

            // remove white spaces and line breaks and add current tag to the html-string
            if ('<![CDATA[' == substr($htmlArrayCurrent, 0, 9) // remove multiple white space in CDATA / XML
                || '<?xml' == substr($htmlArrayCurrent, 0, 5)
            ) {
                $html .= $this->killWhiteSpace($htmlArrayCurrent);
            } else { // remove all line breaks
                $html .= $this->killLineBreaks($htmlArrayCurrent);
            }

            // count up a tab
            if ('<' == substr($htmlArrayCurrent, 0, 1) && '/' != substr($htmlArrayCurrent, 1, 1)) {
                if (' ' !== substr($htmlArrayCurrent, 1, 1)
                    && 'img' !== substr($htmlArrayCurrent, 1, 3)
                    && 'source' !== substr($htmlArrayCurrent, 1, 6)
                    && 'br' !== substr($htmlArrayCurrent, 1, 2)
                    && 'hr' !== substr($htmlArrayCurrent, 1, 2)
                    && 'input' !== substr($htmlArrayCurrent, 1, 5)
                    && 'link' !== substr($htmlArrayCurrent, 1, 4)
                    && 'meta' !== substr($htmlArrayCurrent, 1, 4)
                    && 'col ' !== substr($htmlArrayCurrent, 1, 4)
                    && 'frame' !== substr($htmlArrayCurrent, 1, 5)
                    && 'isindex' !== substr($htmlArrayCurrent, 1, 7)
                    && 'param' !== substr($htmlArrayCurrent, 1, 5)
                    && 'area' !== substr($htmlArrayCurrent, 1, 4)
                    && 'base' !== substr($htmlArrayCurrent, 1, 4)
                    && '<!' !== substr($htmlArrayCurrent, 0, 2)
                    && '<?xml' !== substr($htmlArrayCurrent, 0, 5)
                ) {
                    ++$tabs;
                }
            }
        }

        // Remove empty lines
        if ($this->formatType > 1) {
            $this->removeEmptyLines($html);
        }

        // Restore saved comments, styles and java-scripts
        for ($i = 0; $i < \count($noFormat); ++$i) {
            $html = str_replace("<!-- ELEMENT {$i} -->", $noFormat[$i], $html);
        }

        // include debug comment at the end
        if (0 != $tabs && true === $this->debugComment) {
            $html .= "<!-- {$tabs} open elements found -->";
        }

        return $html;
    }

    /**
     * Remove ALL line breaks and multiple white space.
     *
     * @param string $html
     *
     * @return string
     */
    protected function killLineBreaks($html)
    {
        $html = str_replace($this->newline, '', $html);

        return preg_replace('/\s\s+/u', ' ', $html);
        //? return preg_replace('/\n|\s+(\s)/u', '$1', $html);
    }

    /**
     * Remove multiple white space, keeps line breaks.
     *
     * @param string $html
     *
     * @return string
     */
    protected function killWhiteSpace($html)
    {
        $temp = explode($this->newline, $html);
        for ($i = 0; $i < \count($temp); ++$i) {
            if (!trim($temp[$i])) {
                unset($temp[$i]);
                continue;
            }

            $temp[$i] = trim($temp[$i]);
            $temp[$i] = preg_replace('/\s\s+/', ' ', $temp[$i]);
        }

        return implode($this->newline, $temp);
    }

    /**
     * Remove white space at the end of lines, keeps other white space and line breaks.
     *
     * @param string $html
     *
     * @return string
     */
    protected function rTrimLines(& $html)
    {
        $html = preg_replace('/\s+$/m', '', $html);
    }

    /**
     * Convert newlines according to the current OS.
     *
     * @param string $html
     *
     * @return string
     */
    protected function convNlOs(& $html)
    {
        $html = preg_replace("(\r\n|\r)", $this->newline, $html);
    }

    /**
     * Remove empty lines.
     *
     * @param string $html
     */
    protected function removeEmptyLines(& $html): void
    {
        $temp = explode($this->newline, $html);
        $result = [];
        for ($i = 0; $i < \count($temp); ++$i) {
            if ('' == trim($temp[$i])) {
                continue;
            }
            $result[] = $temp[$i];
        }
        $html = implode($this->newline, $result);
    }

    /**
     * Include configured header comment in HTML content block.
     *
     * @param $html
     */
    public function includeHeaderComment(& $html): void
    {
        $html = preg_replace('/^(-->)$/m', "\n\t".$this->headerComment."\n$1", $html);
    }
}
