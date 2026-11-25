# Dindent

[![Coverage Status](https://coveralls.io/repos/Schleuse/dindent/badge.png?branch=master)](https://coveralls.io/r/Schleuse/dindent?branch=master)
[![Latest Stable Version](https://poser.pugx.org/Schleuse/dindent/version.png)](https://packagist.org/packages/Schleuse/dindent)
[![License](https://poser.pugx.org/Schleuse/dindent/license.png)](https://packagist.org/packages/Schleuse/dindent)

Dindent (aka., "HTML beautifier") will indent HTML for development and testing. Dedicated for those who suffer from reading a template engine produced markup.

## Abuse Case

Dindent will not sanitize or otherwise manipulate your output beyond indentation.

If you are looking to remove malicious code or make sure that your document is standards compliant, consider the following alternatives:

* [HTML Purifier](https://github.com/Exercise/HTMLPurifierBundle)
* [DOMDocument::$formatOutput](http://www.php.net/manual/en/class.domdocument.php)
* [Tidy](http://www.php.net/manual/en/book.tidy.php)

If you only want to indent your code, beware that earlier mentioned libraries will attempt to fix your markup (that's their primary purpose; indentation is a by-product).

## Regex

There is a [good reason not to use regular expression to parse HTML](http://stackoverflow.com/questions/1732348/regex-match-open-tags-except-xhtml-self-contained-tags/1732454#1732454). However, DOM parser will rebuild the whole HTML document. It will add missing tags, close open block tags, or remove anything that's not a valid HTML. This is what Tidy does, DOM, etc. This behavior is undesirable when debugging HTML output. Regex based parser will not rebuild the document. Dindent will only add indentation, without otherwise affecting the markup.

The above is also the reason why [Chrome DevTools](https://developers.google.com/chrome-developer-tools/) is not a direct replacement for Dindent.

## Installation

The recommended way to use Dindent is through [Composer](https://getcomposer.org).

```sh
composer req schleuse/dindent:^3.0
```

## Use

```php
$indenter = new \Gajus\Dindent\Indenter();
$indenter->indent('[..]');
```

<details><summary>In the above example, `[..]` is a placeholder for:</summary>

```html
<!DOCTYPE html>
<html>
<head></head>
<body>
<script>console.log('te> <st')</script>
    <script>
    function test () {
        test; <!-- <a> -->
    }
    </script>
    <div>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
    <div><table border="1" style="background-color: red;"><tr><td>A cell    test!</td>
<td colspan="2" rowspan="2"><table border="1" style="background-color: green;"><tr> <td>Cell</td><td colspan="2" rowspan="2"></td></tr><tr>
        <td><input><input><input></td></tr><tr><td>Cell</td><td>Cell</td><td>Ce
            ll</td></tr></table></td></tr><tr><td>Test <span>Ce       ll</span></td><td>Test <span>Ce ll</span> </td><td>Test <span>Ce </span> ll</td></tr><tr><td>Cell</td><td>Cell</td><td>Cell</td></tr></table></div></div>
    <area>xx</area><xyz/><wbr><hr><hr/>
</body>
</html>
```
</details>
<details><summary>Dindent will convert it to:</summary>

```html
<!DOCTYPE html>
<html>
    <head></head>
    <body>
        <script>console.log('te> <st')</script>
        <script>
    function test () {
        test; <!-- <a> -->
    }
        </script>
        <div>
            <script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
            <div>
                <table border="1" style="background-color: red;">
                    <tr>
                        <td>A cell test!</td>
                        <td colspan="2" rowspan="2">
                            <table border="1" style="background-color: green;">
                                <tr>
                                    <td>Cell</td>
                                    <td colspan="2" rowspan="2"></td>
                                </tr>
                                <tr>
                                    <td>
                                        <input>
                                        <input>
                                        <input>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Cell</td>
                                    <td>Cell</td>
                                    <td>Ce ll</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td>Test <span>Ce ll</span></td>
                        <td>Test <span>Ce ll</span></td>
                        <td>Test <span>Ce</span> ll</td>
                    </tr>
                    <tr>
                        <td>Cell</td>
                        <td>Cell</td>
                        <td>Cell</td>
                    </tr>
                </table>
            </div>
        </div>
        <area>xx</area>
        <xyz/>
        <wbr>
        <hr>
        <hr/>
    </body>
</html>
```
</details>

## Options

`Indenter` constructor accepts the following options that control indentation:

|Name|Description|Default|
|---|---|---|
|`indentation_character`|Character(s) used for indentation. [ info; `null` removes all line-breaks ]|4 space-char|
|`logging`|Keep Track of every transformation and verify. May be memory intensive.|`false`|

### Set element type

HTML elements are either "inline" elements or "block-level" elements.

An inline element occupies only the space bounded by the tags that define the inline element. The following example demonstrates the inline element's influence:

```html
<p>This is an <span>inline</span> element within a block element.</p>
```

A block-level element occupies the entire space of its parent element (container), thereby creating a "block." Browsers typically display the block-level element with a new line both before and after the element. The following example demonstrates the block-level element's influence:

```html
<div>
    <p>This is a block element within a block element.</p>
</div>
```

Dindent identifies the following elements as "inline":

https://github.com/Schleuse/dindent/blob/296a64052963234f822de46f6be81e911c25dd92/src/Indenter.php#L36-L39

This is a [subset of the inline elements](https://developer.mozilla.org/en-US/docs/Web/HTML/Element#inline_text_semantics) ignoring [void elements](https://developer.mozilla.org/en-US/docs/Glossary/Void_element).

All other elements are treated as block.

You can set element type to either block or inline using `setElementType` method:

```php
$indenter = new \Gajus\Dindent\Indenter();
/**
 * @param string $element_name Element name, e.g. "b".
 * @param ELEMENT_TYPE_BLOCK|ELEMENT_TYPE_INLINE $type
 * @return null
 */
$indenter->setElementType('foo', \Gajus\Dindent\Indenter::ELEMENT_TYPE_BLOCK);
$indenter->setElementType('bar', \Gajus\Dindent\Indenter::ELEMENT_TYPE_INLINE);
```

# CLI

Dindent can be used via the CLI script;

```sh
php bin/dindent.php
```

https://github.com/Schleuse/dindent/blob/2ffc1544ba192dad9eb21af9cbce6ab8aae48a36/bin/dindent.php#L14-L24

## Known issues

* Does not treat comments nicely (ToCheck)
