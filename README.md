# EXT:sourceopt

[![Build Status](https://github.com/lochmueller/sourceopt/workflows/Tests/badge.svg)](https://github.com/lochmueller/sourceopt/actions)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/lochmueller/sourceopt/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/lochmueller/sourceopt/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/lochmueller/sourceopt/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/lochmueller/sourceopt/?branch=master)

> Optimization of the final page: reformatting the (x)HTML output, removal of new-lines, comments and generator-info including search and replace strings using your regular expressions. In addition combines all SVG selected within content-elements into one \<symbol\> file and replaces \<img\> by \<use\>.

## Installation

```bash
composer require lochmueller/sourceopt
```

## Configuration

Include the extension and go to the Constant Editor of a template where you find all the possible options under “PLUGIN.SOURCEOPT”.

## Performance

Note: The EXT process need server performance, because there are several search replace operations in the PHP logic of the extension.

## Reference

*Note: The following features are executed in reverse order*

### SourceOpt

| Property                          | Type        | Description                                                      | Default            |
|:----------------------------------|:------------|:-----------------------------------------------------------------|:-------------------|
| sourceopt.enabled                 | boolean     | Is the optimization enabled for this template                    | 1                  |
| sourceopt.removeGenerator         | boolean     | Remove \<meta name="generator" content="TYPO3 CMS"\>             | 1                  |
| sourceopt.removeComments          | boolean     | Remove HTML-Comments                                             | 1                  |
| sourceopt.removeComments.keep     | array       | Spare these listed comments: Regular expressions that match comments that should not be removed. Very useful e.g. to keep the TYPO3SEARCH-Comments so indexed_search can work properly | ``.10``            |
| sourceopt.removeComments.keep.10  | string      | Spare TYPO3SEARCH-Comments from removal                          | /^TYPO3SEARCH_/usi |
| sourceopt.formatHtml              | integer     | Formats the code beautiful and easy readable. New lines and tabs are used in the usual way of illustrating the structure of an XML code. [**Options**](https://github.com/lochmueller/sourceopt/blob/master/Classes/Service/CleanHtmlService.php#L152) | 4                  |
| sourceopt.formatHtml.tabSize      | integer     | Defines the size of the tabs used for formating. If blank one tab is used. If a number is specified the specified number of blank spaces is used instead. This will only work together with `formatHtml` | `[empty]`          |
| sourceopt.formatHtml.debugComment | boolean     | Includes a comment at the end of the html source code that points the numbers of open html tags. This will only work together with `formatHtml` | `[empty]`

### RegEx Replace

#### from [maxserv/replacecontent](https://github.com/MaxServ/t3ext-replacecontent)

- replace `config.tx_replacecontent` to `config.replacer`

#### from [jweiland/replacer](https://github.com/jweiland-net/replacer) `^ 1.4 || < 3.0`

- remove `config.tx_replacer.enable_regex = 1`

- replace `config.tx_replacer` to `config.replacer`

#### You set the search and replace patterns via TypoScript

```
config.replacer {
  search {
    1 = /(?<="|')\/?(fileadmin|typo3temp|uploads)/
    2 = /blabla/
  }
  replace {
    1 = //cdn.tld/$1
    2 < tmp.object
    2.wrap = <b>|</b>
  }
}
```

### SVGstore

| Property                          | Type        | Description                                                      | Default            |
|:----------------------------------|:------------|:-----------------------------------------------------------------|:-------------------|
| svgstore.enabled                  | boolean     | Is the SVG extract & merge enabled for this template             | 1                  |
| svgstore.fileSize                 | integer     | Maximum file size of a SVG to include (in `[byte]`)              | 50000              |

---
##### ToDo:
- Try external packages like https://github.com/ArjanSchouten/HtmlMinifier
