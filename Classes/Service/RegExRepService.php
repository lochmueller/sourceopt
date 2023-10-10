<?php

declare(strict_types=1);

namespace HTML\Sourceopt\Service;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

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

        foreach (['search.', 'replace.'] as $section) {
            if (!isset($config[$section]) || !\is_array($config[$section])) {
                throw new \Exception('missing entry @ config.replacer.'.$section);
            }

            if (preg_match_all('/"([\w\-]+)\.";/', serialize(array_keys($config[$section])), $matches)) {
                $cObj = $cObj ?? $GLOBALS['TSFE']->cObj ?? GeneralUtility::makeInstance(ContentObjectRenderer::class);

                foreach ($matches[1] as $key) {
                    $config[$section][$key] = $cObj
                        ->stdWrap(
                            $config[$section][$key],
                            $config[$section][$key.'.']
                        )
                    ;
                    unset($config[$section][$key.'.']); // keep!
                }
            }

            ksort($config[$section], \SORT_NATURAL); // safety
        }
        unset($cObj, $matches); // save MEM

        if (\TYPO3\CMS\Core\Core\Environment::getContext()->isDevelopment()) {
            foreach ($config['search.'] as $key => $val) {
                if (false === @preg_match($val, '')) {
                    throw new \Exception(preg_last_error_msg().' : please check your regex syntax @ '."{$key} = {$val}");
                }
            }
        }

        $arrIntersectKeysCnt = 2 * \count(array_intersect_key($config['search.'], $config['replace.']));

        if ((bool) (\count($config['search.']) + \count($config['replace.']) - $arrIntersectKeysCnt)) {
            throw new \Exception('config.replacer requests have diverged');
        }

        return preg_replace($config['search.'], $config['replace.'], $html);
    }
}
