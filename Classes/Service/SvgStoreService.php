<?php

declare(strict_types=1);

namespace HTML\Sourceopt\Service;

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class SvgStore ; https://github.com/xerc.
 */
class SvgStoreService implements SingletonInterface
{
    /**
     * SVG-Sprite storage DIR.
     *
     * @var string
     */
    protected $outputDir = '/typo3temp/assets/svg/'; // fallback

    public function __construct()
    {
        //$this->styl = []; # https://stackoverflow.com/questions/39583880/external-svg-fails-to-apply-internal-css
        //$this->defs = []; # https://bugs.chromium.org/p/chromium/issues/detail?id=751733#c14
        $this->svgs = [];

        $this->sitePath = \TYPO3\CMS\Core\Core\Environment::getPublicPath(); // [^/]$

        if (isset($GLOBALS['TSFE']->config['config']['svgstore.']['outputDir']) && !empty($GLOBALS['TSFE']->config['config']['svgstore.']['outputDir'])) {
            $this->outputDir = '/typo3temp/'.$GLOBALS['TSFE']->config['config']['svgstore.']['outputDir'];
        }

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
    $html['body'] = preg_replace_callback('/<img(?<pre>[^>]*)src="(?<src>\/[^"]+\.svg)"(?<post>[^>]*?)[\s\/]*>/s', function (array $matches): string {// ^[/]
      if (!isset($this->svgFileArr[$matches['src']])) {// check usage
        return $matches[0];
      }

        $attr = preg_replace('/\s(?:alt|ismap|loading|title|sizes|srcset|usemap)="[^"]*"/', '', $matches['pre'].$matches['post']); // cleanup

        return sprintf('<svg%s %s><use href="%s#%s"/></svg>', $this->svgFileArr[$matches['src']]['attr'], $attr, $this->spritePath, $this->convertFilePath($matches['src']));
    }, $html['body']);

        // https://developer.mozilla.org/en-US/docs/Web/HTML/Element/object#attributes
    $html['body'] = preg_replace_callback('/<object(?<pre>[^>]*)data="(?<data>\/[^"]+\.svg)"(?<post>[^>]*?)[\s\/]*>(?:<\/object>)/s', function (array $matches): string {// ^[/]
      if (!isset($this->svgFileArr[$matches['data']])) {// check usage
        return $matches[0];
      }

        $attr = preg_replace('/\s(?:form|name|type|usemap)="[^"]*"/', '', $matches['pre'].$matches['post']); // cleanup

        return sprintf('<svg%s %s><use href="%s#%s"/></svg>', $this->svgFileArr[$matches['src']]['attr'], $attr, $this->spritePath, $this->convertFilePath($matches['data']));
    }, $html['body']);

        return $html['head'].$html['body'];
    }

    private function convertFilePath(string $path): string
    {
        return preg_replace('/.svg$|[^\w\-]/', '', str_replace('/', '-', ltrim($path, '/'))); // ^[^/]
    }

    private function addFileToSpriteArr(string $hash, string $path): ?array
    {
        if (1 === preg_match('/;base64/', $svg = file_get_contents($this->sitePath.$path))) {// noop!
            return null;
        }

        if (1 === preg_match('/<(?:style|defs|url\()/', $svg)) {// check links @ __construct
            return null;
        }

        $svg = preg_replace('/<\/svg>.*|xlink:|\s(?:(?:width|height|version|xmlns)|(?:[a-z\-]+\:[a-z\-]+))="[^"]*"/s', '', $svg); // clean !?: \s+(?<atr>[\w\-]+)=["\'](?<val>[^"\']+)["\']

        //$svg = preg_replace('/((?:id|class)=")/','$1'.$hash.'__',$svg);// extend  IDs
        //$svg = preg_replace('/(href="|url\()#/','$1#'.$hash.'__',$svg);// recover IDs

        //$svg = preg_replace_callback('/<style[^>]*>(?<styl>.+?)<\/style>|<defs[^>]*>(?<defs>.+?)<\/defs>/s',function(array $matches) use($hash): string
        //{
        //  if(isset($matches['styl']))
        //  {
        //    $this->styl[] = preg_replace('/\s*(\.|#){1}(.+?)\s*\{/','$1'.$hash.'__$2{',$matches['styl']); // patch CSS # https://mathiasbynens.be/notes/css-escapes
        //  }
        //  if(isset($matches['defs']))
        //  {
        //    $this->defs[] = trim($matches['defs']);
        //  }
        //  return '';
        //},$svg);

        $this->svgs[] = preg_replace('/.*<svg((?:(?!id=)[^>])+)(?:id="[^"]*")?([^>]*>)/s', 'id="'.$this->convertFilePath($path).'"$1$2', $svg, 1); // change ID;

        return preg_match('/\s+viewBox="\s*([+-]?[\d\.]+(?:\s+[+-]?[\d\.]+){3})\s*"/', $svg, $match) ? ['attr' => ' viewBox="'.preg_replace('/\s+/', ' ', $match[1]).'"', 'hash' => $hash] : null;
    }

    private function populateCache(): bool
    {
        $storageArr = $this->getStorageArrayFromDB();
        $svgFileArr = $this->getSvgFilesArrayFromDB(array_keys($storageArr));

        $this->svgFileArr = [];
        foreach ($svgFileArr as $index => $row) {
            if (!$this->svgFileArr[($row['path'] = '/'.$storageArr[$row['storage']].$row['identifier'])] = $this->addFileToSpriteArr($row['sha1'], $row['path'])) {// ^[/]
                unset($this->svgFileArr[$row['path']]);
            }
        }

        $svg = preg_replace_callback(
        '/<use(?<pre>.*?)(?:xlink:)?href="(?<href>\/.+?\.svg)#[^"]+"(?<post>.*?)[\s\/]*>(?:<\/use>)?/s',
        function (array $matches): string {
          return sprintf('<use%s href="#%s"/>', $matches['pre'].$matches['post'], $this->convertFilePath($matches['href']));
      },
        '<svg xmlns="http://www.w3.org/2000/svg">'
      //."\n<style>\n".implode("\n",$this->styl)."\n</style>"
      //."\n<defs>\n".implode("\n",$this->defs)."\n</defs>"
      ."\n<symbol ".implode("</symbol>\n<symbol ", $this->svgs)."</symbol>\n"
      .'</svg>'
    );

        //unset($this->styl);// save MEM
    //unset($this->defs);// save MEM
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
            ->fetchAll()// TODO; use stdClass
        ;
    }
}
