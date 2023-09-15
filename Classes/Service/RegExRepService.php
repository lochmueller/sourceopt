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
            ksort($config[$section]); // only for safety
        }

        $arrIntersectKeysCnt = 2 * \count(array_intersect_key($config['search.'], $config['replace.']));

        if ((bool) (\count($config['search.']) + \count($config['replace.']) - $arrIntersectKeysCnt)) {
            throw new \Exception('search/replace requests have diverged');
        }

        return preg_replace($config['search.'], $config['replace.'], $html);
    }
}
