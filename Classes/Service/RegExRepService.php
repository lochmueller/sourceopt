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
        $config = array_intersect_key($GLOBALS['TSFE']->config['config']['replacer.'], ['search.' => null, 'replace.' => null]);

        if (!isset($config['search.']) || !\is_array($config['search.'])) {
            throw new \Exception('missing entry @ config.replacer.search');
        }
        if (!isset($config['replace.']) || !\is_array($config['replace.'])) {
            throw new \Exception('missing entry @ config.replacer.replace');
        }

        foreach ($config as $section => &$block) {
            foreach ($block as $key => &$regex) {
                if ('search.' == $section
                && (!\is_string($key) || '.' !== $key[-1])
                && !preg_match('/^(.).+\1[a-z]*$/i', $regex)
                ) {
                    throw new \Exception("Please check your RegEx @ {$key} = {$regex}");
                }
                if (isset($config[$section][$key.'.'])) {
                    $regex = $GLOBALS['TSFE']->cObj
                        ->stdWrap(
                            $regex,
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
