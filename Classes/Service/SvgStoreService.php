<?php

declare(strict_types=1);

namespace HTML\Sourceopt\Service;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class SvgStore.
 *
 * @author Marcus Förster ; https://github.com/xerc
 */
class SvgStoreService implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * SVG-Sprite relativ storage directory.
     *
     * @var string
     */
    protected $outputDir = '/typo3temp/assets/svg/';

    /**
     * TYPO3 absolute path to public web.
     *
     * @var string
     */
    protected $sitePath = '';

    /**
     * Final TYPO3 Frontend-Cache object.
     *
     * @var \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend
     */
    protected $svgCache = null;

    /**
     * Cached SVG-Sprite relativ file path.
     *
     * @var string
     */
    protected $spritePath = '';

    /**
     * Cached used SVG files (incl. defs).
     *
     * @var array
     */
    protected $svgFileArr = [];

    /**
     * Final SVG-Sprite Vectors.
     *
     * @var array
     */
    protected $svgs = [];

    /**
     * Final SVG-Sprite Styles.
     *
     * @var array
     */
    protected $styl = []; # ToFix ; https://stackoverflow.com/questions/39583880/external-svg-fails-to-apply-internal-css

    /**
     * Final SVG-Sprite Objects.
     *
     * @var array
     */
    protected $defs = []; # ToFix ; https://bugs.chromium.org/p/chromium/issues/detail?id=751733#c14


    public function __construct()
    {
        $this->sitePath = \TYPO3\CMS\Core\Core\Environment::getPublicPath(); // [^/]$
        $this->svgCache = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class)->getCache('svgstore');

        $this->spritePath = $this->svgCache->get('spritePath') ?: '';
        $this->svgFileArr = $this->svgCache->get('svgFileArr') ?: [];

        if (empty($this->spritePath) && !$this->populateCache()) {
            throw new \Exception('could not write file: '.$this->sitePath.$this->spritePath);
        }

        if (!file_exists($this->sitePath.$this->spritePath)) {
            throw new \Exception('file does not exists: '.$this->sitePath.$this->spritePath);
        }
    }

    public function process(string $html): string
    {
        if ($GLOBALS['TSFE']->config['config']['disableAllHeaderCode'] ?? false) {
            $dom = ['head' => '', 'body' => $html];
        } elseif (!preg_match('/(?<head>.+?<\/head>)(?<body>.+)/s', $html, $dom)) {
            return $html;
        }

        // https://developer.mozilla.org/en-US/docs/Web/HTML/Element/img#attributes
        $dom['body'] = preg_replace_callback('/<img(?<pre>[^>]*)src="(?:https?:)?(?:\/\/[^\/]+?)?(?<src>\/[^"]+\.svg)"(?<post>[^>]*?)[\s\/]*>(?!\s*<\/picture>)/s', function (array $match): string { // ^[/]
            if (!isset($this->svgFileArr[$match['src']])) { // check usage
                return $match[0];
            }
            $attr = preg_replace('/\s(?:alt|ismap|loading|title|sizes|srcset|usemap|crossorigin|decoding|referrerpolicy)="[^"]*"/', '', $match['pre'].$match['post']); // cleanup

            return sprintf('<svg %s %s><use href="%s#%s"/></svg>', $this->svgFileArr[$match['src']]['attr'], trim($attr), $this->spritePath, $this->convertFilePath($match['src']));
        }, $dom['body']);

        // https://developer.mozilla.org/en-US/docs/Web/HTML/Element/object#attributes
        $dom['body'] = preg_replace_callback('/<object(?<pre>[^>]*)data="(?<data>\/[^"]+\.svg)"(?<post>[^>]*?)[\s\/]*>(?:<\/object>)/s', function (array $match): string { // ^[/]
            if (!isset($this->svgFileArr[$match['data']])) { // check usage
                return $match[0];
            }
            $attr = preg_replace('/\s(?:form|name|type|usemap)="[^"]*"/', '', $match['pre'].$match['post']); // cleanup

            return sprintf('<svg %s %s><use href="%s#%s"/></svg>', $this->svgFileArr[$match['data']]['attr'], trim($attr), $this->spritePath, $this->convertFilePath($match['data']));
        }, $dom['body']);

        return $dom['head'].$dom['body'];
    }

    private function convertFilePath(string $path): string
    {
        return preg_replace('/.svg$|[^\w\-]/', '', str_replace('/', '-', ltrim($path, '/'))); // ^[^/]
    }

    private function addFileToSpriteArr(string $hash, string $path, array $attr = []): ?array
    {
        if (!file_exists($this->sitePath.$path)) {
            return null;
        }

        $svg = file_get_contents($this->sitePath.$path);

        if (preg_match('/(?:;base64|i:a?i?pgf)/', $svg)) { // noop!
            return null;
        }

        if (preg_match('/<(?:style|defs)|url\(/', $svg)) {
            return null; // check links @ __construct
        }

        // https://developer.mozilla.org/en-US/docs/Web/SVG/Attribute/xlink:href
        $svg = preg_replace('/^.*?<svg|\s*(<\/svg>)(?!.*\1).*$|xlink:|\s(?:(?:version|xmlns)|(?:[a-z\-]+\:[a-z\-]+))="[^"]*"/s', '', $svg); // cleanup

        // $svg = preg_replace('/(?<=(?:id|class)=")/', $hash.'__', $svg); // extend  IDs
        // $svg = preg_replace('/(?<=href="|url\()#/', $hash.'__', $svg); // recover IDs

        // $svg = preg_replace_callback('/<style[^>]*>(?<styl>.+?)<\/style>|<defs[^>]*>(?<defs>.+?)<\/defs>/s', function(array $match) use($hash): string {
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
        // }, $svg);

        // https://developer.mozilla.org/en-US/docs/Web/SVG/Element/svg#attributes
        $svg = preg_replace_callback('/([^>]*)\s*(?=>)/s', function (array $match) use (&$attr): string {
            if (false === preg_match_all('/(?!\s)(?<attr>[\w\-]+)="\s*(?<value>[^"]+)\s*"/', $match[1], $matches)) {
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
                        if (false !== preg_match('/\S+\s\S+\s\+?(?<width>[\d\.]+)\s\+?(?<height>[\d\.]+)/', $matches['value'][$index], $match)) {
                            $attr[] = sprintf('%s="0 0 %s %s"', $attribute, $match['width'], $match['height']); // save!
                        }
                }
            }

            return implode(' ', $matches[0]);
        }, $svg, 1);

        if (empty($attr)) {
            return null;
        }

        $this->svgs[] = sprintf('id="%s" %s', $this->convertFilePath($path), $svg); // prepend ID

        return ['attr' => implode(' ', $attr), 'hash' => $hash];
    }

    private function populateCache(): bool
    {
        $storageArr = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\StorageRepository::class)->findAll();
        foreach ($storageArr as $storage) {
            if ('relative' == $storage->getConfiguration()['pathType']) {
                $storageArr[$storage->getUid()] = rtrim($storage->getConfiguration()['basePath'], '/'); // [^/]$
            }
        }
        unset($storageArr[0]); // keep!

        $fileArr = GeneralUtility::makeInstance(\HTML\Sourceopt\Resource\SvgFileRepository::class)->findAllByStorageUids(array_keys($storageArr));
        foreach ($fileArr as $file) {
            $file['path'] = '/'.$storageArr[$file['storage']].$file['identifier']; // ^[/]
            $file['defs'] = $this->addFileToSpriteArr($file['sha1'], $file['path']);

            if (null !== $file['defs']) {
                $this->svgFileArr[$file['path']] = $file['defs'];
            }
        }
        unset($storageArr, $storage, $fileArr, $file); // save MEM

        $svg = preg_replace_callback(
            '/<use(?<pre>.*?)(?:xlink:)?href="(?<href>\/.+?\.svg)(?:#[^"]*?)?"(?<post>.*?)[\s\/]*>(?:<\/use>)?/s',
            function (array $match): string {
                if (!isset($this->svgFileArr[$match['href']])) { // check usage
                    return $match[0];
                }

                return sprintf('<use%s href="#%s"/>', $match['pre'].$match['post'], $this->convertFilePath($match['href']));
            },
            '<svg xmlns="http://www.w3.org/2000/svg">'
            // ."\n<style>\n".implode("\n", $this->styl)."\n</style>"
            // ."\n<defs>\n".implode("\n", $this->defs)."\n</defs>"
            ."\n<symbol ".implode("</symbol>\n<symbol ", $this->svgs)."</symbol>\n"
            .'</svg>'
        );

        // unset($this->styl); // save MEM
        // unset($this->defs); // save MEM
        unset($this->svgs); // save MEM

        if ($GLOBALS['TSFE']->config['config']['sourceopt.']['formatHtml'] ?? false) {
            $svg = preg_replace('/(?<=>)\s+(?=<)/', '', $svg); // remove emptiness
            $svg = preg_replace('/[\t\v]/', ' ', $svg); // prepare shrinkage
            $svg = preg_replace('/\s{2,}/', ' ', $svg); // shrink whitespace
        }

        $svg = preg_replace('/<([a-z]+)\s*(\/|>\s*<\/\1)>\s*|\s+(?=\/>)/i', '', $svg); // remove emtpy TAGs & shorten endings
        $svg = preg_replace('/<((circle|ellipse|line|path|polygon|polyline|rect|stop|use)\s[^>]+?)\s*>\s*<\/\2>/', '<$1/>', $svg); // shorten/minify TAG syntax

        if (!is_dir($this->sitePath.$this->outputDir)) {
            GeneralUtility::mkdir_deep($this->sitePath.$this->outputDir);
        }

        $this->spritePath = $this->outputDir.hash('sha1', serialize($this->svgFileArr)).'.svg';
        if (false === file_put_contents($this->sitePath.$this->spritePath, $svg)) {
            return false;
        }

        $this->svgCache->set('spritePath', $this->spritePath);
        $this->svgCache->set('svgFileArr', $this->svgFileArr);

        return true;
    }
}
