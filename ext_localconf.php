<?php defined('TYPO3') || die();

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
