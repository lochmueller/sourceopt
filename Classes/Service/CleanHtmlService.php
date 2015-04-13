<?php
namespace HTML\Sourceopt\Service;

/**
 * Service: Clean parsed HTML functionality
 * Based on the extension 'sourceopt'

 */
class CleanHtmlService implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * Enable Debug comment in footer
	 *
	 * @var boolean
	 */
	protected $debugComment = FALSE;

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
	 * Enable/disable UTF8 support
	 *
	 * @var boolean
	 */
	protected $utf8 = TRUE;

	/**
	 * Configured extra header comment
	 *
	 * @var string
	 */
	protected $headerComment = '';

	/**
	 * Enable/disable removal of generator tag
	 *
	 * @var boolean
	 */
	protected $removeGenerator = TRUE;

	/**
	 * Enable/disable removal of comments
	 *
	 * @var boolean
	 */
	protected $removeComments = TRUE;

	/**
	 * Enable/disable removal of blur scripts
	 *
	 * @var boolean
	 */
	protected $removeBlurScript = TRUE;

	/**
	 * Patterns for white-listing comments inside content
	 *
	 * @var array
	 */
	protected $whiteListCommentsPatterns = array();

	/**
	 * Set variables based on given config
	 *
	 * @param array $config
	 *
	 * @return void
	 */
	public function setVariables(array $config) {
		switch (TYPO3_OS) { // set newline
			case 'WIN' :
				$this->newline = "\r\n";
				break;
			default :
				$this->newline = "\n";
		}

		if (!empty($config)) {
			if ((bool) $config['enabled'] === FALSE) {
				return;
			}

			if ($config['formatHtml'] && is_numeric($config['formatHtml'])) {
				$this->formatType = (int) $config['formatHtml'];
			}

			if ($config['formatHtml.']['tabSize'] && is_numeric($config['formatHtml.']['tabSize'])) {
				$this->tab = str_pad('', $config['formatHtml.']['tabSize'], ' ');
			}

			if (isset($config['enable_utf'])) {
				$this->utf8 = (bool) $config['enable_utf-8_support'];
			}

			if (isset($config['formatHtml.']['debugComment'])) {
				$this->debugComment = (bool) $config['debugComment'];
			}

			if (isset($config['headerComment'])) {
				$this->headerComment = $config['headerComment'];
			}

			if (isset($config['removeGenerator'])) {
				$this->removeGenerator = (bool) $config['removeGenerator'];
			}

			if (isset($config['removeComments'])) {
				$this->removeComments = (bool) $config['removeComments'];

				if (isset($config['removeComments.'])) {
					$this->whiteListCommentsPatterns = $config['removeComments.']['keep.'];
				}
			}

			if (isset($config['removeBlurScript'])) {
				$this->removeBlurScript = (bool) $config['removeBlurScript'];
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
	public function clean(&$html, $config = array()) {
		if (!empty($config)) {
			$this->setVariables($config);
		}

		if (TRUE === $this->removeGenerator) {
			$this->removeGenerator($html);
		}

		if (TRUE === $this->removeComments) {
			$this->removeComments($html);
		}

		if (TRUE === $this->removeBlurScript) {
			$this->removeBlurScript($html);
		}

		if (!empty($this->headerComment)) {
			$this->includeHeaderComment($html);
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
	protected function formatHtml(&$html) {
		// Save original formated comments, pre, textarea, styles and java-scripts & replace them with markers
		preg_match_all('/(?s)((<!--.*?-->)|(<[ \n\r]*pre[^>]*>.*?<[ \n\r]*\/pre[^>]*>)|(<[ \n\r]*textarea[^>]*>.*?<[ \n\r]*\/textarea[^>]*>)|(<[ \n\r]*style[^>]*>.*?<[ \n\r]*\/style[^>]*>)|(<[ \n\r]*script[^>]*>.*?<[ \n\r]*\/script[^>]*>))/im', $html, $matches);
		$no_format = $matches[0]; // do not format these block elements
		for ($i = 0; $i < count($no_format); $i++) {
			$html = str_replace($no_format[$i], "\n<!-- ELEMENT $i -->", $html);
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
		$html_array_temp = preg_split('/(<(?:[^<>]+(?:"[^"]*"|\'[^\']*\')?)+>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
		// remove empty lines
		$html_array = array('');
		$z = 1;
		for ($x = 0; $x < count($html_array_temp); $x++) {
			$t = trim($html_array_temp[$x]);
			if ($t !== '') {
				$html_array[$z] = $html_array_temp[$x];
				$z++;
				// if the trimmed line was empty but the original wasn't, search for inline element closing tags in the last $html_array element
			} else {
				// if ($t !== $html_array_temp[$x] && preg_match('/<\/' . $inlineElements . '( .*)? >/Usi', $html_array[$z - 1]) === 1)
				$html_array[$z] = ' ';
				$z++;
			}
		}

		// rebuild html
		$html = '';
		$tabs = 0;
		for ($x = 0; $x < count($html_array); $x++) {
			// check if the element should stand in a new line
			$newline = FALSE;
			if (substr($html_array[$x - 1], 0, 5) == '<?xml') {
				$newline = TRUE;
			} elseif ($this->formatType == 2 && ( // minimalistic line break
					# this element has a line break before itself
					preg_match('/<' . $structureBoxLikeElements . '(.*)>/Usi', $html_array[$x]) || preg_match('/<' . $structureBoxLikeElements . '(.*) \/>/Usi', $html_array[$x]) || # one element before is a element that has a line break after
					preg_match('/<\/' . $structureBoxLikeElements . '(.*)>/Usi', $html_array[$x - 1]) || substr($html_array[$x - 1], 0, 4) == '<!--' || preg_match('/<' . $structureBoxLikeElements . '(.*) \/>/Usi', $html_array[$x - 1]))
			) {
				$newline = TRUE;
			} elseif ($this->formatType == 3 && ( // aestetic line break
					# this element has a line break before itself
					preg_match('/<' . $esteticBoxLikeElements . '(.*)>/Usi', $html_array[$x]) || preg_match('/<' . $esteticBoxLikeElements . '(.*) \/>/Usi', $html_array[$x]) || # one element before is a element that has a line break after
					preg_match('/<\/' . $esteticBoxLikeElements . '(.*)>/Usi', $html_array[$x - 1]) || substr($html_array[$x - 1], 0, 4) == '<!--' || preg_match('/<' . $esteticBoxLikeElements . '(.*) \/>/Usi', $html_array[$x - 1]))
			) {
				$newline = TRUE;
			} elseif ($this->formatType >= 4 && ( // logical line break
					# this element has a line break before itself
					preg_match('/<' . $allBoxLikeElements . '(.*)>/Usi', $html_array[$x]) || preg_match('/<' . $allBoxLikeElements . '(.*) \/>/Usi', $html_array[$x]) || # one element before is a element that has a line break after
					preg_match('/<\/' . $allBoxLikeElements . '(.*)>/Usi', $html_array[$x - 1]) || substr($html_array[$x - 1], 0, 4) == '<!--' || preg_match('/<' . $allBoxLikeElements . '(.*) \/>/Usi', $html_array[$x - 1]))
			) {
				$newline = TRUE;
			}

			// count down a tab
			if (substr($html_array[$x], 0, 2) == '</') {
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
			if (substr($html_array[$x - 1], 0, 4) == '<pre' // remove white space after line ending in PRE / TEXTAREA / comment
				|| substr($html_array[$x - 1], 0, 9) == '<textarea' || substr($html_array[$x - 1], 0, 4) == '<!--'
			) {
				$html .= $this->rTrimLines($html_array[$x]);
			} elseif (substr($html_array[$x], 0, 9) == '<![CDATA[' // remove multiple white space in CDATA / XML
				|| substr($html_array[$x], 0, 5) == '<?xml'
			) {
				$html .= $this->killWhiteSpace($html_array[$x]);
			} else { // remove all line breaks
				$html .= $this->killLineBreaks($html_array[$x]);
			}

			// count up a tab
			if (substr($html_array[$x], 0, 1) == '<' && substr($html_array[$x], 1, 1) != '/') {
				if (substr($html_array[$x], 1, 1) != ' ' && substr($html_array[$x], 1, 3) != 'img' && substr($html_array[$x], 1, 2) != 'br' && substr($html_array[$x], 1, 2) != 'hr' && substr($html_array[$x], 1, 5) != 'input' && substr($html_array[$x], 1, 4) != 'link' && substr($html_array[$x], 1, 4) != 'meta' && substr($html_array[$x], 1, 4) != 'col ' && substr($html_array[$x], 1, 5) != 'frame' && substr($html_array[$x], 1, 7) != 'isindex' && substr($html_array[$x], 1, 5) != 'param' && substr($html_array[$x], 1, 4) != 'area' && substr($html_array[$x], 1, 4) != 'base' && substr($html_array[$x], 0, 2) != '<!' && substr($html_array[$x], 0, 5) != '<?xml'
				) {
					$tabs++;
				}
			}
		}

		// Restore saved comments, styles and java-scripts
		for ($i = 0; $i < count($no_format); $i++) {
			$no_format[$i] = $this->rTrimLines($no_format[$i]); // remove white space after line ending
			$html = str_replace("<!-- ELEMENT $i -->", $no_format[$i], $html);
		}

		// include debug comment at the end
		if ($tabs != 0 && $this->debugComment === TRUE) {
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
	protected function killLineBreaks($html) {
		$html = $this->convNlOs($html);
		$html = str_replace($this->newline, "", $html);
		// remove double empty spaces
		if ($this->utf8 == TRUE) {
			$html = preg_replace('/\s\s+/u', ' ', $html);
		} else {
			$html = preg_replace('/\s\s+/', ' ', $html);
		}
		return $html;
	}

	/**
	 * Remove multiple white space, keeps line breaks
	 *
	 * @param string $html
	 *
	 * @return string
	 */
	protected function killWhiteSpace($html) {
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
	protected function rTrimLines($html) {
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
	protected function convNlOs($html) {
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
	protected function trimLines(&$html) {
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
	protected function removeEmptyLines(&$html) {
		$temp = explode($this->newline, $html);
		$result = array();
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
	protected function removeNewLines(&$html) {
		$splitArray = array(
			'textarea',
			'pre'
		); // eventuell auch: span, script, style
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
	protected function removeLinkSchema(&$html) {
		$html = preg_replace("/<link rel=\"?schema.dc\"?.+?>/is", "", $html);
	}

	/**
	 * Remove empty alt tags
	 *
	 * @param string $html
	 *
	 * @return void
	 */
	protected function removeEmptyAltAtr(&$html) {
		$html = str_replace("alt=\"\"", "", $html);
	}

	/**
	 * Remove broken links in <a> tags
	 *
	 * @param string $html
	 *
	 * @return void
	 */
	protected function removeRealUrlBrokenRootLink(&$html) {
		$html = str_replace('href=".html"', 'href=""', $html);
	}

	/**
	 * Remove all comments except the whitelisted comments
	 *
	 * @param string $html
	 *
	 * @return void
	 */
	protected function removeComments(&$html) {
		// match all styles, scripts and comments
		$matches = array();
		preg_match_all('/(?s)((<!--.*?-->)|(<[ \n\r]*style[^>]*>.*?<[ \n\r]*\/style[^>]*>)|(<[ \n\r]*script[^>]*>.*?<[ \n\r]*\/script[^>]*>))/im', $html, $matches);
		foreach ($matches[0] as $tag) {
			if ($this->keepComment($tag) === FALSE) {
				$html = str_replace($tag, '', $html);
			}
		}
	}

	/**
	 * Check if a comment is defined to be kept in a pattern whiteListOfComments
	 *
	 * @param string $commentHtml
	 *
	 * @return boolean
	 */
	protected function keepComment($commentHtml) {
		// if not even a comment, skip this
		if (!preg_match('/^\<\!\-\-(.*?)\-\-\>$/usi', $commentHtml)) {
			return TRUE;
		}

		// if not defined in white list
		if (!empty($this->whiteListCommentsPatterns)) {
			$commentHtml = str_replace("<!--", "", $commentHtml);
			$commentHtml = str_replace("-->", "", $commentHtml);
			$commentHtml = trim($commentHtml);
			foreach ($this->whiteListCommentsPatterns as $pattern) {
				if (preg_match($pattern, $commentHtml)) {
					return TRUE;
				}
			}
		}
		return FALSE;
	}

	/**
	 * TYPO3 adds to each page a small script:
	 *                <script language="javascript">
	 *                <!--
	 *                browserName = navigator.appName;
	 *                browserVer = parseInt(navigator.appVersion);
	 *                var msie4 = (browserName == "Microsoft Internet Explorer" && browserVer >= 4);
	 *                if ((browserName == "Netscape" && browserVer >= 3) || msie4 || browserName=="Konqueror") {version = "n3";} else {version = "n2";}
	 *                function blurLink(theObject){
	 *                if (msie4){theObject.blur();}
	 *                }
	 *                // -->
	 *                </script>
	 * Obviously used for client-side browserdetection - but thats not necessary if your page doesn't use JS
	 *
	 * @param string $html
	 *
	 * @return void
	 */
	function removeBlurScript(&$html) {
		if (strlen($html) < 100000) {
			$pattern = '/<script (type="text\/javascript"|language="javascript")>.+?Konqueror.+function blurLink.+theObject.blur.+?<\/script>/is';
			$html = preg_replace($pattern, '', $html); // in head
		}
		$html = str_replace(' onfocus="blurLink(this);"', '', $html); // in body
	}

	/**
	 * Remove the generator Tag
	 *
	 * @param string $html
	 *
	 * @return void
	 */
	public function removeGenerator(&$html) {
		$html = preg_replace('/<meta name=\"?generator\"?.+?>/is', '', $html);
	}

	/**
	 * Include configured header comment in HTML content block
	 *
	 * @param $html
	 */
	public function includeHeaderComment(&$html) {
		if (!empty($this->headerComment)) {
			$html = preg_replace_callback(
				'/<meta http-equiv(.*)>/Usi',
				function ($matches) {
					return trim($matches[0] . $this->newline . $this->tab . $this->tab . '<!-- ' . $this->headerComment . '-->');
				},
				$html
			);
		}
	}
}
