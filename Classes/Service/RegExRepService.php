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
            foreach ($block as $key => &$val) {
                if (isset($config[$section][$key.'.'])) {
                    $val = $GLOBALS['TSFE']->cObj
                        ->stdWrap(
                            $val,
                            $config[$section][$key.'.']
                        )
                    ;
                    unset($config[$section][$key.'.']); // keep!
                }
                if ('search.' == $section
                && (!\is_string($key) || '.' !== $key[-1])
                && false === @preg_match($val, '')// HACKy
                ) {
                    throw new \Exception(preg_last_error_msg()." : please check your regex syntax @ {$key} = {$val}");
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
