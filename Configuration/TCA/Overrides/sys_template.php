<?php defined('TYPO3') || die();

// Add static template configuration
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'sourceopt',
    'Configuration/TypoScript',
    'Sourceopt'
);
