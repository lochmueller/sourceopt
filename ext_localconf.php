<?php defined('TYPO3') || die();

// Load bundled dependencies in non-composer mode (classic installation)
if (!\TYPO3\CMS\Core\Core\Environment::isComposerMode() && !class_exists(\Gajus\Dindent\Indenter::class)) {
    require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('sourceopt') . 'Resources/Private/Php/vendor/autoload.php';
}

// SvgStore Cache
if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['svgstore'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['svgstore'] =
    [
        'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
        'backend' => \TYPO3\CMS\Core\Cache\Backend\NullBackend::class,
        'options' => [
            'defaultLifetime' => 0,
        ]
    ];
}
