.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt
.. index:: TypoScript Reference for sourceopt

TypoScript Reference for sourceopt
----------------------------------

All configuration of sourceopt (beside the obsolete) can be done via constant editor (“PLUGIN.SOURCEOPT”)

Certainly you also can set the configuration directly via TypoScript setup.

Here is a reference that is valid for both, TypoScript setup and Constant Editor:

+-----------------------------------+-------------+------------------------------------------------------------------+--------------------+
| Property                          | Data type   | Description                                                      | Default            |
+===================================+=============+==================================================================+====================+
| sourceopt.enabled                 | boolean     | Is the optimization enabled for this template                    | 1                  |
+-----------------------------------+-------------+------------------------------------------------------------------+--------------------+
| sourceopt.removeGenerator         | boolean     | Remove <meta name="generator" content="TYPO3 x.x CMS">           | 1                  |
+-----------------------------------+-------------+------------------------------------------------------------------+--------------------+
| sourceopt.removeComments          | boolean     | Remove HTML-Comments                                             | 1                  |
+-----------------------------------+-------------+------------------------------------------------------------------+--------------------+
| sourceopt.removeComments.keep.10  | boolean     | Spare this comments from remove: Regular expressions that match  | /^TYPO3SEARCH_/usi |
|                                   |             | comments that should not be removed. Very useful e.g. to keep    |                    |
|                                   |             | the TYPO3SEARCH-Comments so indexed_search can work properly     |                    |
+-----------------------------------+-------------+------------------------------------------------------------------+--------------------+
| sourceopt.formatHtml              | integer     | Formats the code beautiful and easy readable.                    | 4                  |
|                                   |             | New lines and tabs are used in the usual way of illustrating the |                    |
|                                   |             | structure of an XML code.                                        |                    |
|                                   |             |                                                                  |                    |
|                                   |             | This is a great help for developing and checking the code as     |                    |
|                                   |             | well as compressing the code that it is faster transferred to    |                    |
|                                   |             | the user agent.                                                  |                    |
|                                   |             |                                                                  |                    |
|                                   |             | **Options:**                                                     |                    |
|                                   |             |                                                                  |                    |
|                                   |             | * 0 = off                                                        |                    |
|                                   |             | * 1 = no line break at all (code in one line)                    |                    |
|                                   |             | * 2 = minimalistic line breaks (structure defining box-elements) |                    |
|                                   |             | * 3 = aesthetic line breaks (important box-elements)             |                    |
|                                   |             | * 4 = logic line breaks (all box-elements)                       |                    |
+-----------------------------------+-------------+------------------------------------------------------------------+--------------------+
| sourceopt.formatHtml.tabSize      | integer     | Defines the size of the tabs used for formating. If blank one    | ``[empty]``        |
|                                   |             | tab is used. If a number is specified the specified number of    |                    |
|                                   |             | blank spaces is used instead. This will only work together with  |                    |
|                                   |             | formatHtml                                                       |                    |
+-----------------------------------+-------------+------------------------------------------------------------------+--------------------+
| sourceopt.formatHtml.debugComment | boolean     | Includes a comment at the end of the html source code that       | ``[empty]``        |
|                                   |             | points the numbers of open html tags. This will only work        |                    |
|                                   |             | together with formatHtml                                         |                    |
+-----------------------------------+-------------+------------------------------------------------------------------+--------------------+

TypoScript Reference for svgstore
---------------------------------

+-----------------------------------+-------------+------------------------------------------------------------------+--------------------+
| Property                          | Data type   | Description                                                      | Default            |
+===================================+=============+==================================================================+====================+
| svgstore.enabled                  | boolean     | Is the SVG extract & merge enabled for this template             | 1                  |
+-----------------------------------+-------------+------------------------------------------------------------------+--------------------+
| svgstore.fileSize                 | boolean     | Maximum file size of a SVG to include (in ``[byte]``)            | 50000              |
+-----------------------------------+-------------+------------------------------------------------------------------+--------------------+
