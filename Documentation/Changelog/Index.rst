.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt
.. index:: Changelog

Changelog
---------

** newer versions **

Check the github history: https://github.com/lochmueller/sourceopt

**0.8.6 – 9.4.2015**

Reinstated the TypoScriptFrontend controller hook for correct rendering of both cached and non-cached variables.

All deprecated features like headerComment were reinstated too.

**0.8.0 – 6.11.2014**

Rewrite: Rewritten the extension. PageRenderer hook is now being used instead of the TypoScriptFrontend controller.

**0.5.12 – 21.7.2009**

bugfix: COA\_/ USER\_INT Objects will now also be passed through
sourceopt.

**0.5.8 – 11.2.2009**

utf-8 support is now optional, thanks to Rene Stäker.

**0.5.8 – 6.2.2009**

Bugfix: utf8 letters

**0.5.7 – 30.1.2009**

Bugfix: long html outputs where killed

New: removing of obsolete functionality

New: general code cleanup

**0.5.6**

Bugfix: pass by reference bug – thanks toMathias Brodala

**0.5.5**

Bugfix: “$”

**0.5.2**

Bugfix: compatibility to typo3 Version 4.2.0

Bugfix: compatibility to xx\_INT Objects

**0.5.1**

Bugfix: also non cached output should be transformed via sourceopt

**0.5.0**

Bugfix/Change: using an fe-hook instead of xclass, there for the
extension now is more compatible and the result of sourceopt is
cached. An esseantial feature if using with nc\_staticfilecache (or
fl\_staticfilecache)

New: debugComment

Change: conditional comments for InternetExplorer are now saved by
default

**0.4.6**

Change: update of manual, adaption of constant-editor

Bugfix: FormatHTML: Changed the tab-increase-pattern from <col\* to
<col\s\*, so that you no longer get a tab increase on <colgroup\*>.
(thanks to Tilman Moser)

Bugfix/Change: FormatHTML ignores now Comments / Styles and Scripts,
since Scripts intended to break, when you deal with html-Code as
Strings in Javascripts. (thanks to Tilman Moser)

New: If tabSize is specified in conf-Array, tab is n x Space, else
it's \t (thanks to Tilman Moser)

**0.4.5**

New: removeRealUrlBrokenRootLink

New: formatHtml uses tabs instead of white space for formatting. That
leads to smaller files

New: formatHtml removes double blank space

Bugfix: line 309

Bugfix:keepComment.10

Bugfix:keep line breaks in <script and <![CDATA[

New: MoveInlineCSS supports the field MoveInlineCSS.media as
string/stdwrap, resulting as attribute like <link rel="stylesheet"
href="xyz.css" media="string text" /> (thanks to Daniel Wegener)

Bugfix: formatHTML 4 will no longer eat spaces before and after
inline-tags (changed: killLineBreaks does no longer trim(), just
killing linebreaks) (thanks to Daniel Wegener)

Bugfix: The starting xml-tag of XHTML-Documents will no longer be
handled as (unclosed) box-element. (thanks to Daniel Wegener)

Change: Some code-cleanup. (thanks to Daniel Wegener)

**0.4.1 - 0.4.4**

Bugfix: minor bugs

**0.4.0**

Change:names of the objects with no "\_" but with humps like normally
in Typo3

Change:default TS includes some Typo3-core options that help cleaning
the code

Change: TYPO3 Copyright stays in the code even when header comments
are removed. It still can be removed by deleting one line in the TS
default configuration.

New:moveInlineCss, moveInlineCssFile = cause it does in some regard a
better job than config.inlineStyle2TempFile

New: removeComments = 3 (only Head),

New: keepComment = an array to define which comments should be saved
from removeComments (useful for indexed\_search),

New: headerComment = an additional header comment

New: formatHtml = Formats the code beautiful and easy readable.

Bugfix:conflicts with some combination of options

(by Ronald Steiner)

**0.3.1**

Bugfix: Some options didn't have any effect although enabled

**0.3**

Bugfix: The option trim\_lines now also removes tabs. The option
remove\_tabs has been removed.

Change: The option keep\_typo3\_copyright has been removed (see next
point)

Change: Removed three options: remove\_script\_cdata,
move\_inline\_css, move\_inline\_css\_file, as these options are now
in the core-engine included and remove\_script\_cdata removed the JS
for spamProtection too (thanks to Brikou and Christian Meyer)

Change: To remove comments you have now three options: don't remove
them, remove them only from the body or everywhere (thanks to Bernd
Hanisch)

Bugfix: The option remove\_new\_lines now also removes space between
tags that don't contain any string

New: Remove breaks, empty lines and trim lines: you can limit it to
the body or still do it everywhere (thanks to Bernd Hanisch)

Change: Some general improvements of source code (thanks to Bernd
Hanisch)

**0.2.2**

New option: alternate\_html\_xhtml\_language

**0.2.1**

New option: Keep the copyright comment if you remove comments

New option: Replace the clear.gif with a string eg. 1.gif

New option: Remove attributes from the <body>

Bugfix: It's now also possible to remove the blur-script under typo
3.6.0 RC1
