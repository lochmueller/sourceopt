<?php

declare(strict_types=1);

namespace HTML\Sourceopt\Service;

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class SvgStore.
 *
 * @author Marcus Förster ; https://github.com/xerc
 */
class SvgStoreService implements SingletonInterface
{
    public function __construct()
    {
        //$this->styl = []; # https://stackoverflow.com/questions/39583880/external-svg-fails-to-apply-internal-css
        //$this->defs = []; # https://bugs.chromium.org/p/chromium/issues/detail?id=751733#c14
        $this->svgs = [];

        $this->outputDir = '/typo3temp/assets/svg/';
        $this->sitePath = \TYPO3\CMS\Core\Core\Environment::getPublicPath(); // [^/]$

        $this->connPool = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class);
        $this->svgCache = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class)->getCache('svgstore');
    }

    public function process(string $html): string
    {
        $this->spritePath = $this->svgCache->get('spritePath');
        $this->svgFileArr = $this->svgCache->get('svgFileArr');

        if (empty($this->spritePath) && !$this->populateCache()) {
            throw new \Exception('could not write file: '.$this->sitePath.$this->spritePath);
        }

        if (!file_exists($this->sitePath.$this->spritePath)) {
            throw new \Exception('file does not exists: '.$this->sitePath.$this->spritePath);
        }

        if (!preg_match('/(?<head>.+?<\/head>)(?<body>.+)/s', $html, $html) && 5 == \count($html)) {
            throw new \Exception('fix HTML!');
        }

        // https://developer.mozilla.org/en-US/docs/Web/HTML/Element/img#attributes
        $html['body'] = preg_replace_callback('/<img(?<pre>[^>]*)src="(?<src>\/[^"]+\.svg)"(?<post>[^>]*?)[\s\/]*>(?!\s*<\/picture>)/s', function (array $match): string { // ^[/]
            if (!isset($this->svgFileArr[$match['src']])) { // check usage
                return $match[0];
            }
            $attr = preg_replace('/\s(?:alt|ismap|loading|title|sizes|srcset|usemap)="[^"]*"/', '', $match['pre'].$match['post']); // cleanup

            return sprintf('<svg %s %s><use href="%s#%s"/></svg>', $this->svgFileArr[$match['src']]['attr'], $attr, $this->spritePath, $this->convertFilePath($match['src']));
        }, $html['body']);

        // https://developer.mozilla.org/en-US/docs/Web/HTML/Element/object#attributes
        $html['body'] = preg_replace_callback('/<object(?<pre>[^>]*)data="(?<data>\/[^"]+\.svg)"(?<post>[^>]*?)[\s\/]*>(?:<\/object>)/s', function (array $match): string { // ^[/]
            if (!isset($this->svgFileArr[$match['data']])) { // check usage
                return $match[0];
            }
            $attr = preg_replace('/\s(?:form|name|type|usemap)="[^"]*"/', '', $match['pre'].$match['post']); // cleanup

            return sprintf('<svg %s %s><use href="%s#%s"/></svg>', $this->svgFileArr[$match['src']]['attr'], $attr, $this->spritePath, $this->convertFilePath($match['data']));
        }, $html['body']);

        return $html['head'].$html['body'];
    }

    private function convertFilePath(string $path): string
    {
        return preg_replace('/.svg$|[^\w\-]/', '', str_replace('/', '-', ltrim($path, '/'))); // ^[^/]
    }

    private function addFileToSpriteArr(string $hash, string $path): ?array
    {
        if (1 === preg_match('/;base64/', $svg = file_get_contents($this->sitePath.$path))) { // noop!
            return null;
        }

        if (1 === preg_match('/<(?:style|defs)|url\(/', $svg)) {
            return null; // check links @ __construct
        }

        //$svg = preg_replace('/((?:id|class)=")/', '$1'.$hash.'__', $svg); // extend  IDs
        //$svg = preg_replace('/(href="|url\()#/', '$1#'.$hash.'__', $svg); // recover IDs

        //$svg = preg_replace_callback('/<style[^>]*>(?<styl>.+?)<\/style>|<defs[^>]*>(?<defs>.+?)<\/defs>/s', function(array $match) use($hash): string {
        //
        //    if(isset($match['styl']))
        //    {
        //        $this->styl[] = preg_replace('/\s*(\.|#){1}(.+?)\s*\{/', '$1'.$hash.'__$2{', $match['styl']); // patch CSS # https://mathiasbynens.be/notes/css-escapes
        //    }
        //    if(isset($match['defs']))
        //    {
        //        $this->defs[] = trim($match['defs']);
        //    }
        //    return '';
        //}, $svg);

        // https://developer.mozilla.org/en-US/docs/Web/SVG/Attribute/xlink:href
        $svg = preg_replace('/.*<svg|<\/svg>.*|xlink:|\s(?:(?:version|xmlns)|(?:[a-z\-]+\:[a-z\-]+))="[^"]*"/s', '', $svg); // cleanup

        // https://developer.mozilla.org/en-US/docs/Web/SVG/Element/svg#attributes
        $svg = preg_replace_callback('/([^>]+)\s*(?=>)/s', function (array $match) use (&$attr): string {
            if (false === preg_match_all('/\s(?<attr>[\w\-]+)=["\']\s*(?<value>[^"\']+)\s*["\']/', $match[1], $matches)) {
                return $match[0];
            }
            foreach ($matches['attr'] as $index => $attribute) {
                switch ($attribute) {
                  case 'id':
                  case 'width':
                  case 'height':
                      unset($matches[0][$index]);
                      break;

                  case 'viewBox':
                      $attr[] = sprintf('%s="%s"', $attribute, $matches['value'][$index]); // save!
                      // no break
                  default:
                      $matches[0][$index] = sprintf('%s="%s"', $attribute, $matches['value'][$index]); // cleanup
                }
            }

            return implode(' ', $matches[0]);
        }, $svg, 1);

        if ($attr) { // TODO; beautify
            $this->svgs[] = sprintf('id="%s" %s', $this->convertFilePath($path), $svg); // append ID
        }

        return !$attr ?: ['attr' => implode(' ', $attr), 'hash' => $hash];
    }

    private function populateCache(): bool
    {
        $storageArr = $this->getStorageArrayFromDB();
        $svgFileArr = $this->getSvgFilesArrayFromDB(array_keys($storageArr));

        $this->svgFileArr = [];
        foreach ($svgFileArr as $index => $row) {
            if (!$this->svgFileArr[($row['path'] = '/'.$storageArr[$row['storage']].$row['identifier'])] = $this->addFileToSpriteArr($row['sha1'], $row['path'])) { // ^[/]
                unset($this->svgFileArr[$row['path']]);
            }
        }

        $svg = preg_replace_callback(
            '/<use(?<pre>.*?)(?:xlink:)?href="(?<href>\/.+?\.svg)#[^"]+"(?<post>.*?)[\s\/]*>(?:<\/use>)?/s',
            function (array $match): string {
                if (!isset($this->svgFileArr[$match['href']])) { // check usage
                    return $match[0];
                }
                return sprintf('<use%s href="#%s"/>', $match['pre'].$match['post'], $this->convertFilePath($match['href']));
            },
            '<svg xmlns="http://www.w3.org/2000/svg">'
            //."\n<style>\n".implode("\n", $this->styl)."\n</style>"
            //."\n<defs>\n".implode("\n", $this->defs)."\n</defs>"
            ."\n<symbol ".implode("</symbol>\n<symbol ", $this->svgs)."</symbol>\n"
            .'</svg>'
        );

        //unset($this->styl); // save MEM
        //unset($this->defs); // save MEM
        unset($this->svgs); // save MEM

        if (\is_int($var = $GLOBALS['TSFE']->config['config']['sourceopt.']['formatHtml']) && 1 == $var) {
            $svg = preg_replace('/[\n\r\t\v\0]|\s{2,}/', '', $svg);
        }

        $svg = preg_replace('/<([a-z]+)\s*(\/|>\s*<\/\1)>\s*/i', '', $svg); // remove emtpy
        $svg = preg_replace('/<((circle|ellipse|line|path|polygon|polyline|rect|stop|use)\s[^>]+?)\s*>\s*<\/\2>/', '<$1/>', $svg); // shorten/minify

        if (!is_dir($this->sitePath.$this->outputDir)) {
            GeneralUtility::mkdir_deep($this->sitePath.$this->outputDir);
        }

        $this->spritePath = $this->outputDir.hash('sha1', serialize($this->svgFileArr)).'.svg';
        if (false === file_put_contents($this->sitePath.$this->spritePath, $svg)) {
            return false;
        }
        unset($svg); // save MEM

        $this->svgCache->set('svgFileArr', $this->svgFileArr);
        $this->svgCache->set('spritePath', $this->spritePath);

        return true;
    }

    private function getStorageArrayFromDB(): array
    {
        $storageResources = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\StorageRepository::class)->findAll();
        foreach ($storageResources as $storage) {
            if ('relative' == $storage->getConfiguration()['pathType']) {
                $storageResources[$storage->getUid()] = rtrim($storage->getConfiguration()['basePath'], '/'); // [^/]$
            }
        }
        unset($storageResources[0]); // keep!

        return $storageResources;
    }

    private function getSvgFilesArrayFromDB(array $storageIds): array
    {
        return ($queryBuilder = $this->connPool->getQueryBuilderForTable('sys_file'))
            ->select('sys_file.storage', 'sys_file.identifier', 'sys_file.sha1')
            ->from('sys_file')
            ->innerJoin(
                'sys_file',
                'sys_file_reference',
                'sys_file_reference',
                $queryBuilder->expr()->eq(
                    'sys_file_reference.uid_local',
                    $queryBuilder->quoteIdentifier('sys_file.uid')
                )
            )
            ->where(
                $queryBuilder->expr()->in('sys_file.storage', $queryBuilder->createNamedParameter($storageIds, \TYPO3\CMS\Core\Database\Connection::PARAM_INT_ARRAY)),
                $queryBuilder->expr()->eq('sys_file.mime_type', $queryBuilder->createNamedParameter('image/svg+xml')),
                $queryBuilder->expr()->lt('sys_file.size', $queryBuilder->createNamedParameter($GLOBALS['TSFE']->config['config']['svgstore.']['fileSize'])),
            )
            ->groupBy('sys_file.uid')
            ->orderBy('sys_file.uid')
            ->execute()
            ->fetchAll() // TODO; use stdClass
        ;
    }
}
