# EXT:sourceopt

[![Build Status](https://github.com/lochmueller/sourceopt/workflows/Tests/badge.svg)](https://github.com/lochmueller/sourceopt/actions)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/lochmueller/sourceopt/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/lochmueller/sourceopt/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/lochmueller/sourceopt/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/lochmueller/sourceopt/?branch=master)

- [SourceOpt](#sourceopt) : reformatting the (x)HTML output & removal of new-lines, comments and generator-info
- [RegExRep](#regex-replace) : search and replace strings using your regular expressions ; [embrace regex](https://www.regular-expressions.info) and [migrate now](#howto-migrate)
- [SVGstore](#svgstore) : combines all SVG selected within elements into one \<symbol\> file and replaces \<img\> by \<use\>

## Version

#### >= 5.2.2
https://github.com/lochmueller/sourceopt/blob/173f7bd2a44b546844961ced1f0831371badd620/composer.json#L8-L9

#### <= 5.2.0 (legacy)
https://github.com/lochmueller/sourceopt/blob/6663a8a8512ba280bc7e8b8d38610146495a94ce/composer.json#L8-L9

## Installation

```bash
composer require lochmueller/sourceopt
```
- via *TypoScript*

  `[constants]`
  ```
  @import 'EXT:sourceopt/Configuration/TypoScript/constants'
  ```
  `[setup]`
  ```
  @import 'EXT:sourceopt/Configuration/TypoScript/setup'
  ```
- via input device
  -  add `[EXT:sourceopt/Configuration/TypoScript]` into **`Include static`** at `Includes` in `Template`

## Configuration

Include the extension and go to the `Constant Editor` of a template where you find all options under `PLUGIN`

## Performance

The PHP process need server performance, because there are several search/replace operations in the logic

## Reference

Note: The following features are executed in reverse order


### SourceOpt

*TypoScript* `[constants]` || prepend `config.` at `[setup]`
| Property                          | Type        | Description                                                      | Default            |
|:----------------------------------|:------------|:-----------------------------------------------------------------|:-------------------|
| sourceopt.enabled                 | boolean     | Is the optimization enabled for this template                    | 1                  |
| sourceopt.removeGenerator         | boolean     | Remove \<meta name="generator" content="TYPO3 CMS"\>             | 1                  |
| sourceopt.removeComments          | boolean     | Remove HTML-Comments                                             | 1                  |
| sourceopt.removeComments.keep     | array       | Spare these listed comments: Regular expressions that match comments that should not be removed. Very useful e.g. to keep the TYPO3SEARCH-Comments so indexed_search can work properly | ``.10``            |
| sourceopt.removeComments.keep.10  | string      | Spare TYPO3SEARCH-Comments from removal                          | /^TYPO3SEARCH_/usi |
| sourceopt.headerComment           | string      | Your additional (appended) header comment                        | `[empty]`          |
| sourceopt.formatHtml              | integer     | Formats the code beautiful and easy readable. New lines and tabs are used in the usual way of illustrating the structure of an XML code. <details><summary>**Options**</summary>https://github.com/lochmueller/sourceopt/blob/2346673ee51d2b64308e1ddb1433cea2f37eafcb/Classes/Service/CleanHtmlService.php#L156-L161</details> | 4                  |
| sourceopt.formatHtml.tabSize      | integer     | Defines the size of the tabs used for formating. If blank one tab is used. If a number is specified the specified number of blank spaces is used instead. This will only work together with `formatHtml` | `[empty]`          |
| sourceopt.formatHtml.debugComment | boolean     | Includes a comment at the end of the html source code that points the numbers of open html tags. This will only work together with `formatHtml` | `[empty]`


### RegEx Replace

*TypoScript* `[setup]` incl. [`stdWrap`](https://docs.typo3.org/m/typo3/reference-typoscript/main/en-us/Functions/Stdwrap.html)
```
config.replacer {
  search {
    1 = /(?<="|')\/?(fileadmin|typo3temp|uploads)/

    give-me-cherries = /fruit/

    wrapBoldly < tmp.find
    wrapBoldly.wrap = /|/
  }
  replace {
    1 = //cdn.tld/$1

    give-me-cherries = cherry

    wrapBoldly < tmp.repl
    wrapBoldly.wrap = <b>|</b>
  }
}
```
Note: both arrays will be [ksort](https://www.php.net/manual/de/function.ksort.php)'ed in "[natural order](https://www.php.net/manual/en/function.natsort.php#refsect1-function.natsort-examples)"


<details><summary>

#### HowTo migrate

</summary>
<details><summary>

##### from [jweiland/replacer](https://github.com/jweiland-net/replacer) : `1.4 - 2.x`

</summary>

- regex replace `config\.tx_(?:\w*replace\w*)` to `config.replacer`
- remove `config.tx_replacer.enable_regex = 1`

</details>
<details><summary>

##### from [maxserv/replacecontent](https://github.com/MaxServ/t3ext-replacecontent) 2013 - 2017

</summary>

- regex replace `config\.tx_(?:\w*replace\w*)` to `config.replacer`

</details>
<details><summary>

##### from [typo3-ter/ja_replacer](https://extensions.typo3.org/extension/ja_replacer) 2009 - 2013 || [phorax/ja-replacer](https://github.com/phorax/ja-replacer/) 2016

</summary>

```bash
composer install jweiland/replacer
```
& replace `config.tx_ja_replacer` to `config.tx_replacer`

</details>
<details><summary>

##### from [typo3-ter/n84_contentreplacer](https://extensions.typo3.org/extension/n84_contentreplacer) 2016

</summary>

- regex replace `config\.tx_(?:\w*replace\w*)` to `config.replacer`

</details>
<details><summary>

##### from [typo3-ter/fereplace](https://extensions.typo3.org/extension/fereplace) 2011

</summary>

```bash
composer install jweiland/replacer
```
& replace `plugin.fereplace.pairs` to `config.tx_replacer` .. ah .. and one regex
- search `(\n\s*)(\d+)(?:{\s+|\.)old\s*=\s*([^\n]+).+?new\s*=\s*([^\n]+)`
- replace `$1search.$2  = $3\n$1replace.$2 =  $4`

</details>
<details><summary>

##### from [typo3-ter/regex](https://extensions.typo3.org/extension/regex) 2009

</summary>

- regex replace `config\.regex` to `config.replacer`
- regex replace `(?:\s)(\w+)\s*=\s*` to `search.$1  = `
- regex replace `(\w+)\.replacement\s*=\s*` to `replace.$1 =  `

</details>
<details><summary>

##### from any other tool or just for regex

</summary>

- regex replace `(?:config|plugin)\.tx_any_other_tool` to `config.replacer`
- inside block `search`
  - regex replace `\/` to `\\/` (carefully)
  - regex replace `\s*=\s*(.+)` to `  = /$1/`
- inside block `replace`
  - regex replace `\s*=\s*(.+)` to ` =  $1`
  - consider a PR for conversion specifics

</details>
</details>


### SVGstore

*TypoScript* `[constants]` || prepend `config.` at `[setup]`
| Property                          | Type        | Description                                                      | Default            |
|:----------------------------------|:------------|:-----------------------------------------------------------------|:-------------------|
| svgstore.enabled                  | boolean     | Is the SVG extract & merge enabled for this template             | 1                  |
| svgstore.fileSize                 | integer     | Maximum file size of a SVG to include (in `[byte]`)              | 50000              |

---
##### ToDo:
- Try external packages like https://github.com/ArjanSchouten/HtmlMinifier
