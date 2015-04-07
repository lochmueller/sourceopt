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


TypoScript setup for sourceopt:
-------------------------------

All configuration of sourceopt (beside the obsolete) can be done via
constant editor (“PLUGIN.SOURCEOPT”)

Certainly you also can set the configuration directly via TypoScript
setup.

Here is an example of a TypoScript setup:

config.sourceopt {

	enabled = 1

	enable_utf-8_support = 1

	formatHtml = 2

	formatHtml {

		tabSize =

		debugComment = 0

	}

}