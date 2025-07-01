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
     */
    protected bool $debugComment = false;

    /**
     * Format Type.
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
     * Clean given HTML with formatter.
     */
    public function clean(string $html, array $config = []): string
    {
        if (!empty($config)) {
            $this->setVariables($config);
        }

        // convert line-breaks to UNIX
        $html = preg_replace("(\r\n|\r)", $this->newline, $html);

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
            $html = preg_replace('/^(-->)$/m', "\n\t" . $this->headerComment . "\n$1", $html, 1);
        }

        // cleanup HTML5 self-closing elements
        if (!isset($GLOBALS['TSFE']->config['config']['doctype'])
            || 'x' !== substr($GLOBALS['TSFE']->config['config']['doctype'], 0, 1)) {
            $html = preg_replace(
                '/<((?:area|base|br|col|embed|hr|img|input|link|meta|param|source|track|wbr)\s[^>]+?)\s*\\\?\/>/',
                '<$1>',
                $html
            );
        }

        if ($this->formatType) {
            $indenter = new \Gajus\Dindent\Indenter(['indentation_character' => $this->tab]);
            $html = $indenter->indent($html);
        }

        // recover line-breaks
        if (Environment::isWindows()) {
            $html = str_replace($this->newline, "\r\n", $html);
        }

        return (string) $html;
    }
}
