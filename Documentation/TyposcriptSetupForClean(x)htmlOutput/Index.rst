.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt
.. index:: TypoScript setup for clean (X)HTML output


TypoScript setup for clean (X)HTML output
-----------------------------------------

TYPO3 brings many TypoScript options to get a clean (x)html output -
even without sourceopt.

Here some examples:

config:
::

    config {
        # switch the doctype to XHTML Strict:
        #"xhtml_trans", "xhtml_frames", "xhtml_strict", "xhtml_basic", "xhtml_11", "xhtml_2", "none"
        doctype = xhtml_strict

        # remove <?xml... ?> prolog
        xmlprologue = none

        #XHTML cleaning:
        xhtml_cleaning = all

        #remove default JavaScript:
        removeDefaultJS = 1

        # place the removed JavaScript in an external file:
        #removeDefaultJS = external

        # move css in an external file:
        inlineStyle2TempFile = 1
        # disable some of the comments:
        disablePrefixComment = 1

        # UTF8 Output:
        metaCharset = utf-8
        additionalHeaders = Content-Type:text/html;charset=utf-8

        # Additional Parameter in the <a>-Tag
        ATagParams =
    }

plugin:
::

    plugin {
        # deletes the default css from some the css_styled_content (be aware what you lose)
        tx_cssstyledcontent._CSS_DEFAULT_STYLE >
    }

tt_content:
::

    tt_content {
        # removes a-tag anchors
        # stdWrap.dataWrap >

        # removes various prefix comments
        stdWrap.prefixComment >
        header.20.dataWrap >
        header.20.prefixComment >
        default.prefixComment >
        text.stdWrap.prefixComment >
        text.20.prefixComment >
        textpic.20.stdWrap.prefixComment >
        table.20.stdWrap.prefixComment >
        mailform.20.stdWrap.wrap >
        menu.20.stdWrap.prefixComment >
        image.20.stdWrap.prefixComment >
        list.20.stdWrap.prefixComment >

        # remove clear.gif
        image.20.spaceBelowAbove = 0
        header.stdWrap.space = 0|0
        stdWrap.space = 0|0
        stdWrap.spaceBefore = 0
        stdWrap.spaceAfter = 0
        stdWrap.space = 0|0
        # remove clear.gif after headlines
        text.20.parseFunc.tags.typohead.stdWrap.space = 0|0

        # remove atributes for p and pre-tags
        text.20.parseFunc.nonTypoTagStdWrap.encapsLines.addAttributes.P.style=
        text.20.parseFunc.nonTypoTagStdWrap.encapsLines.addAttributes.PRE.style=
    }
