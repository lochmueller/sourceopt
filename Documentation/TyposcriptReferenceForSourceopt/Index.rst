.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. ==================================================
.. DEFINE SOME TEXTROLES
.. --------------------------------------------------
.. role::   underline
.. role::   typoscript(code)
.. role::   ts(typoscript)
:class:  typoscript
.. role::   php(code)


TypoScript Reference for Sourceopt:
-----------------------------------

All configuration of sourceopt (beside the obsolete) can be done via
constant editor (“PLUGIN.SOURCEOPT”)

Certainly you also can set the configuration directly via TypoScript
setup.

Here is a reference that is valid for both, TypoScript setup and
Constant Editor:

.. ### BEGIN~OF~TABLE ###

.. container:: table-row

   Property
         Property:

   Data type
         Data type:

   Description
         Description:

   Default
         Default:


.. container:: table-row

   Property
         enabled

   Data type
         boolean

   Description
         Is the optimization enabled for this template

   Default
         1


.. container:: table-row

   Property
         enable\_utf-8\_support

   Data type
         boolean

   Description
         Enable UTF-8 support: Disable if your website doesn't use UTF-8 and
         you have a problem with empty strings

   Default
         1

.. container:: table-row

   Property
         formatHtml

   Data type
         integer

   Description
         Formats the code beautiful and easy readable. New lines and tabs are
         used in the usual way of illustrating the structure of an XML code.
         This is a great help for developing and checking the code (Option 4 or
         5) as well as compressing the code that it is faster transferred to
         the user agent (Option 1 or 2).

         **Options:**

         *0 = off*

         *1= no line break at all (code in one line)*

         *2 = minimalistic line breaks (structure defining box-elements)*

         *3 = aesthetic line breaks (important box-elements)*

         *4 = logic line breaks (all box-elements)*

   Default
         4


.. container:: table-row

   Property
         formatHtml.tabSize

   Data type
         integer

   Description
         Defines the size of the tabs used for formating. If blank one tab is
         used. If a number is specified the specified number of blank spaces is
         used instead. This will only work together with formatHtml

   Default
         [EMPTY]


.. container:: table-row

   Property
         formatHtml.debugComment

   Data type
         boolean

   Description
         Includes a comment at the end of the html source code that points the
         numbers of open html tags. This will only work together with
         formatHtml

   Default
         0

.. ###### END~OF~TABLE ######


