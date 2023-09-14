<?php

declare(strict_types=1);

namespace HTML\Sourceopt\Service;

/**
 * Class RegExRepService.
 *
 * @author Marcus Förster ; https://github.com/xerc
 */
class RegExRepService implements \TYPO3\CMS\Core\SingletonInterface
{
    public function process(string $html): string
    {
        $config = $GLOBALS['TSFE']->config['config']['replacer.'];
        unset($config['enabled']); // keep!

        foreach ($config as $section => &$block) {
            foreach ($block as $key => &$content) {
                if (isset($config[$section][$key.'.'])) {
                    $content = $GLOBALS['TSFE']->cObj
                        ->stdWrap(
                            $content,
                            $config[$section][$key.'.']
                        )
                    ;
                    unset($config[$section][$key.'.']); // keep!
                }
            }
            ksort($config[$section]);
        }

        if (\count($config['search.']) !== \count($config['replace.'])) {
            return $html;
        }

        return preg_replace($config['search.'], $config['replace.'], $html);
    }
}
