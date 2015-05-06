.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt
.. index:: TypoScript setup for sourceopt


TypoScript setup for sourceopt
------------------------------

All configuration of sourceopt (beside the obsolete) can be done via
constant editor (“PLUGIN.SOURCEOPT”)

Certainly you also can set the configuration directly via TypoScript
setup.

Here is an example of a TypoScript configuration:

via constants:
::

    sourceopt {
        enabled = 1
        enable_utf-8_support = 1
        formatHtml = 2
    }

via setup:
::

    config.sourceopt {
        enabled = 1
        enable_utf-8_support = 1
        formatHtml = 2
    }