<?php

namespace HTML\Sourceopt\Service;

use HTML\Sourceopt\Manipulation\ManipulationInterface;
use HTML\Sourceopt\Manipulation\RemoveBlurScript;
use HTML\Sourceopt\Manipulation\RemoveComments;
use HTML\Sourceopt\Manipulation\RemoveGenerator;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service: Clean parsed HTML functionality
 * Based on the extension 'sourceopt'
 */
class CleanHtmlService implements SingletonInterface
{

    /**
     * Enable Debug comment in footer
     *
     * @var boolean
     */
    protected $debugComment = false;

    /**
     * Format Type
     *
     * @var integer
     */
    protected $formatType = 2;

    /**
     * Tab character
     *
     * @var string
     */
    protected $tab = "\t";

    /**
     * Newline character
     *
     * @var string
     */
    protected $newline = "\n";

    /**
     * Configured extra header comment
     *
     * @var string
     */
    protected $headerComment = '';

    /**
     * Set variables based on given config
     *
     * @param array $config
     *
     * @return void
     */
    public function setVariables(array $config)
    {
        switch (TYPO3_OS) { // set newline
            case 'WIN':
                $this->newline = "\r\n";
                break;
            default:
                $this->newline = "\n";
        }

        if (!empty($config)) {
            if ($config['formatHtml'] && is_numeric($config['formatHtml'])) {
                $this->formatType = (int)$config['formatHtml'];
            }

            if ($config['formatHtml.']['tabSize'] && is_numeric($config['formatHtml.']['tabSize'])) {
                $this->tab = str_pad('', $config['formatHtml.']['tabSize'], ' ');
            }

            if (isset($config['formatHtml.']['debugComment'])) {
                $this->debugComment = (bool)$config['formatHtml.']['debugComment'];
            }

            if (isset($config['headerComment'])) {
                $this->headerComment = $config['headerComment'];
            }
        }
    }

    /**
     * Clean given HTML with formatter
     *
     * @param string $html
     * @param array $config
     *
     * @return void
     */
    public function clean(&$html, $config = [])
    {
        if (!empty($config)) {
            if ((bool)$config['enabled'] === false) {
                return;
            }

            $this->setVariables($config);
        }

        $manipulations = [];

        if (isset($config['removeGenerator']) && (bool)$config['removeGenerator']) {
            $manipulations['removeGenerator'] = GeneralUtility::makeInstance(RemoveGenerator::class);
        }

        if (isset($config['removeComments']) && (bool)$config['removeComments']) {
            $manipulations['removeComments'] = GeneralUtility::makeInstance(RemoveComments::class);
        }

        if (isset($config['removeBlurScript']) && (bool)$config['removeBlurScript']) {
            $manipulations['removeBlurScript'] = GeneralUtility::makeInstance(RemoveBlurScript::class);
        }

        if (!empty($this->headerComment)) {
            $this->includeHeaderComment($html);
        }

        foreach ($manipulations as $key => $manipulation) {
            /** @var ManipulationInterface $manipulation */
            $configuration = isset($config[$key . '.']) && is_array($config[$key . '.']) ? $config[$key . '.'] : [];
            $html = $manipulation->manipulate($html, $configuration);
        }

        if ($this->formatType) {
            $this->formatHtml($html);
        }
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
     *    5 => max line breaks (all elements)
     *
     * @param string $html
     *
     * @return void
     */
    protected function formatHtml(&$html)
    {
        // Save original formated comments, pre, textarea, styles and java-scripts & replace them with markers
        preg_match_all(
            '/(?s)((<!--.*?-->)|(<[ \n\r]*pre[^>]*>.*?<[ \n\r]*\/pre[^>]*>)|(<[ \n\r]*textarea[^>]*>.*?<[ \n\r]*\/textarea[^>]*>)|(<[ \n\r]*style[^>]*>.*?<[ \n\r]*\/style[^>]*>)|(<[ \n\r]*script[^>]*>.*?<[ \n\r]*\/script[^>]*>))/im',
            $html,
            $matches
        );
        $noFormat = $matches[0]; // do not format these block elements
        for ($i = 0; $i < count($noFormat); $i++) {
            $html = str_replace($noFormat[$i], "\n<!-- ELEMENT $i -->", $html);
        }

        // define box elements for formatting
        $trueBoxElements = 'address|blockquote|center|dir|div|dl|fieldset|form|h1|h2|h3|h4|h5|h6|hr|isindex|menu|noframes|noscript|ol|p|pre|table|ul|article|aside|details|figcaption|figure|footer|header|hgroup|menu|nav|section';
        $functionalBoxElements = 'dd|dt|frameset|li|tbody|td|tfoot|th|thead|tr|colgroup';
        $usableBoxElements = 'applet|button|del|iframe|ins|map|object|script';
        $imagineBoxElements = 'html|body|head|meta|title|link|script|base|!--';
        $allBoxLikeElements = '(?>' . $trueBoxElements . '|' . $functionalBoxElements . '|' . $usableBoxElements . '|' . $imagineBoxElements . ')';
        $esteticBoxLikeElements = '(?>html|head|body|meta name|title|div|table|h1|h2|h3|h4|h5|h6|p|form|pre|center|!--)';
        $structureBoxLikeElements = '(?>html|head|body|div|!--)';

        // split html into it's elements
        $htmlArrayTemp = preg_split(
            '/(<(?:[^<>]+(?:"[^"]*"|\'[^\']*\')?)+>)/',
            $html,
            -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
        );
        // remove empty lines
        $htmlArray = [''];
        $z = 1;
        for ($x = 0; $x < count($htmlArrayTemp); $x++) {
            $t = trim($htmlArrayTemp[$x]);
            if ($t !== '') {
                $htmlArray[$z] = $htmlArrayTemp[$x];
                $z++;
            } else {
                $htmlArray[$z] = ' ';
                $z++;
            }
        }

        // rebuild html
        $html = '';
        $tabs = 0;
        for ($x = 0; $x < count($htmlArray); $x++) {
            // check if the element should stand in a new line
            $newline = false;
            if (substr($htmlArray[$x - 1], 0, 5) == '<?xml') {
                $newline = true;
            } elseif ($this->formatType == 2 && ( // minimalistic line break
                    # this element has a line break before itself
                    preg_match(
                        '/<' . $structureBoxLikeElements . '(.*)>/Usi',
                        $htmlArray[$x]
                    ) || preg_match(
                        '/<' . $structureBoxLikeElements . '(.*) \/>/Usi',
                        $htmlArray[$x]
                    ) || # one element before is a element that has a line break after
                    preg_match(
                        '/<\/' . $structureBoxLikeElements . '(.*)>/Usi',
                        $htmlArray[$x - 1]
                    ) || substr(
                        $htmlArray[$x - 1],
                        0,
                        4
                    ) == '<!--' || preg_match('/<' . $structureBoxLikeElements . '(.*) \/>/Usi', $htmlArray[$x - 1]))
            ) {
                $newline = true;
            } elseif ($this->formatType == 3 && ( // aestetic line break
                    # this element has a line break before itself
                    preg_match(
                        '/<' . $esteticBoxLikeElements . '(.*)>/Usi',
                        $htmlArray[$x]
                    ) || preg_match(
                        '/<' . $esteticBoxLikeElements . '(.*) \/>/Usi',
                        $htmlArray[$x]
                    ) || # one element before is a element that has a line break after
                    preg_match('/<\/' . $esteticBoxLikeElements . '(.*)>/Usi', $htmlArray[$x - 1]) || substr(
                        $htmlArray[$x - 1],
                        0,
                        4
                    ) == '<!--' || preg_match('/<' . $esteticBoxLikeElements . '(.*) \/>/Usi', $htmlArray[$x - 1]))
            ) {
                $newline = true;
            } elseif ($this->formatType >= 4 && ( // logical line break
                    # this element has a line break before itself
                    preg_match(
                        '/<' . $allBoxLikeElements . '(.*)>/Usi',
                        $htmlArray[$x]
                    ) || preg_match(
                        '/<' . $allBoxLikeElements . '(.*) \/>/Usi',
                        $htmlArray[$x]
                    ) || # one element before is a element that has a line break after
                    preg_match('/<\/' . $allBoxLikeElements . '(.*)>/Usi', $htmlArray[$x - 1]) || substr(
                        $htmlArray[$x - 1],
                        0,
                        4
                    ) == '<!--' || preg_match('/<' . $allBoxLikeElements . '(.*) \/>/Usi', $htmlArray[$x - 1]))
            ) {
                $newline = true;
            }

            // count down a tab
            if (substr($htmlArray[$x], 0, 2) == '</') {
                $tabs--;
            }

            // add tabs and line breaks in front of the current tag
            if ($newline) {
                $html .= $this->newline;
                for ($y = 0; $y < $tabs; $y++) {
                    $html .= $this->tab;
                }
            }

            // remove white spaces and line breaks and add current tag to the html-string
            if (substr($htmlArray[$x - 1], 0, 4) == '<pre' // remove white space after line ending in PRE / TEXTAREA / comment
                || substr($htmlArray[$x - 1], 0, 9) == '<textarea' || substr($htmlArray[$x - 1], 0, 4) == '<!--'
            ) {
                $html .= $this->rTrimLines($htmlArray[$x]);
            } elseif (substr($htmlArray[$x], 0, 9) == '<![CDATA[' // remove multiple white space in CDATA / XML
                || substr($htmlArray[$x], 0, 5) == '<?xml'
            ) {
                $html .= $this->killWhiteSpace($htmlArray[$x]);
            } else { // remove all line breaks
                $html .= $this->killLineBreaks($htmlArray[$x]);
            }

            // count up a tab
            if (substr($htmlArray[$x], 0, 1) == '<' && substr($htmlArray[$x], 1, 1) != '/') {
                if (substr($htmlArray[$x], 1, 1) != ' ' && substr($htmlArray[$x], 1, 3) != 'img' && substr(
                        $htmlArray[$x],
                        1,
                        2
                    ) != 'br' && substr($htmlArray[$x], 1, 2) != 'hr' && substr(
                        $htmlArray[$x],
                        1,
                        5
                    ) != 'input' && substr($htmlArray[$x], 1, 4) != 'link' && substr(
                        $htmlArray[$x],
                        1,
                        4
                    ) != 'meta' && substr($htmlArray[$x], 1, 4) != 'col ' && substr(
                        $htmlArray[$x],
                        1,
                        5
                    ) != 'frame' && substr($htmlArray[$x], 1, 7) != 'isindex' && substr(
                        $htmlArray[$x],
                        1,
                        5
                    ) != 'param' && substr($htmlArray[$x], 1, 4) != 'area' && substr(
                        $htmlArray[$x],
                        1,
                        4
                    ) != 'base' && substr($htmlArray[$x], 0, 2) != '<!' && substr($htmlArray[$x], 0, 5) != '<?xml'
                ) {
                    $tabs++;
                }
            }
        }

        // Remove empty lines
        if ($this->formatType > 1) {
            $this->removeEmptyLines($html);
        }

        // Restore saved comments, styles and java-scripts
        for ($i = 0; $i < count($noFormat); $i++) {
            $noFormat[$i] = $this->rTrimLines($noFormat[$i]); // remove white space after line ending
            $html = str_replace("<!-- ELEMENT $i -->", $noFormat[$i], $html);
        }

        // include debug comment at the end
        if ($tabs != 0 && $this->debugComment === true) {
            $html .= '<!--' . $tabs . " open elements found-->\r\n";
        }
    }

    /**
     * Remove ALL line breaks and multiple white space
     *
     * @param string $html
     *
     * @return string
     */
    protected function killLineBreaks($html)
    {
        $html = $this->convNlOs($html);
        $html = str_replace($this->newline, "", $html);
        $html = preg_replace('/\s\s+/u', ' ', $html);
        return $html;
    }

    /**
     * Remove multiple white space, keeps line breaks
     *
     * @param string $html
     *
     * @return string
     */
    protected function killWhiteSpace($html)
    {
        $html = $this->convNlOs($html);
        $temp = explode($this->newline, $html);
        for ($i = 0; $i < count($temp); $i++) {
            if (!trim($temp[$i])) {
                unset($temp[$i]);
            } else {
                $temp[$i] = trim($temp[$i]);
                $temp[$i] = preg_replace('/\s\s+/', ' ', $temp[$i]);
            }
        }
        $html = implode($this->newline, $temp);
        return $html;
    }

    /**
     * Remove white space at the end of lines, keeps other white space and line breaks
     *
     * @param string $html
     *
     * @return string
     */
    protected function rTrimLines($html)
    {
        $html = $this->convNlOs($html);
        $temp = explode($this->newline, $html);
        for ($i = 0; $i < count($temp); $i++) {
            $temp[$i] = rtrim($temp[$i]);
        }
        $html = implode($this->newline, $temp);
        return $html;
    }

    /**
     * Convert newlines according to the current OS
     *
     * @param string $html
     *
     * @return string
     */
    protected function convNlOs($html)
    {
        $html = preg_replace("(\r\n|\n|\r)", $this->newline, $html);
        return $html;
    }

    /**
     * Remove tabs and empty spaces before and after lines, transforms linebreaks system conform
     *
     * @param string $html Html-Code
     *
     * @return void
     */
    protected function trimLines(&$html)
    {
        $html = str_replace("\t", "", $html);
        // convert newlines according to the current OS
        if (TYPO3_OS == "WIN") {
            $html = str_replace("\n", "\r\n", $html);
        } else {
            $html = str_replace("\r\n", "\n", $html);
        }
        $temp = explode($this->newline, $html);
        $temp = array_map('trim', $temp);
        $html = implode($this->newline, $temp);
        unset($temp);
    }

    /**
     * Remove empty lines
     *
     * @param string $html
     *
     * @return void
     */
    protected function removeEmptyLines(&$html)
    {
        $temp = explode($this->newline, $html);
        $result = [];
        for ($i = 0; $i < count($temp); ++$i) {
            if ("" == trim($temp[$i])) {
                continue;
            }
            $result[] = $temp[$i];
        }
        $html = implode($this->newline, $result);
    }

    /**
     * Remove new lines where unnecessary
     * spares line breaks within: pre, textarea, ...
     *
     * @param string $html
     *
     * @return void
     */
    protected function removeNewLines(&$html)
    {
        $splitArray = [
            'textarea',
            'pre'
        ]; // eventuell auch: span, script, style
        $peaces = preg_split('#(<(' . implode('|', $splitArray) . ').*>.*</\2>)#Uis', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        $html = "";
        for ($i = 0; $i < count($peaces); $i++) {
            if (($i + 1) % 3 == 0) {
                continue;
            }
            $html .= (($i - 1) % 3 != 0) ? $this->killLineBreaks($peaces[$i]) : $peaces[$i];
        }
    }

    /**
     * Remove obsolete link schema
     *
     * @param string $html
     *
     * @return void
     */
    protected function removeLinkSchema(&$html)
    {
        $html = preg_replace("/<link rel=\"?schema.dc\"?.+?>/is", "", $html);
    }

    /**
     * Remove empty alt tags
     *
     * @param string $html
     *
     * @return void
     */
    protected function removeEmptyAltAtr(&$html)
    {
        $html = str_replace("alt=\"\"", "", $html);
    }

    /**
     * Remove broken links in <a> tags
     *
     * @param string $html
     *
     * @return void
     */
    protected function removeRealUrlBrokenRootLink(&$html)
    {
        $html = str_replace('href=".html"', 'href=""', $html);
    }

    /**
     * Include configured header comment in HTML content block
     *
     * @param $html
     */
    public function includeHeaderComment(&$html)
    {
        if (!empty($this->headerComment)) {
            $html = preg_replace_callback('/<meta http-equiv(.*)>/Usi', function ($matches) {
                return trim($matches[0] . $this->newline . $this->tab . $this->tab . '<!-- ' . $this->headerComment . '-->');
            }, $html, 1);
        }
    }
}
