<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

/**
 * Hook for HTML-modification on the page
 */
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'][] = \HTML\Sourceopt\User\FrontendHook::class . '->cleanUncachedContent';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all'][] = \HTML\Sourceopt\User\FrontendHook::class . '->cleanCachedContent';
