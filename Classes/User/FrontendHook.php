<?php
namespace HTML\Sourceopt\User;

use HTML\Sourceopt\Service\CleanHtmlService;

/**
 * Hook: Front end rendering
 *
 * @package HTML\Sourceopt\User
 */
class FrontendHook implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var \HTML\Sourceopt\Service\CleanHtmlService
	 * @inject
	 */
	protected $cleanHtmlService = NULL;

	/**
	 * Initialize needed variables
	 */
	public function __construct() {
		$this->initialize();
	}

	/**
	 * Hook for adjusting the HTML <body> output
	 *
	 * @param \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $typoScriptFrontend
	 *
	 * @return void
	 */
	public function clean(\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController &$typoScriptFrontend) {
		if ($this->cleanHtmlService instanceof CleanHtmlService) {
			$configuration = $typoScriptFrontend->config['config']['sourceopt.'];
			$this->cleanHtmlService->clean($typoScriptFrontend->content, $configuration);
		}
	}

	/**
	 * Clean cache content from FrontendRenderer
	 * hook is called after Caching!
	 * => for modification of pages with COA_/USER_INT objects.
	 *
	 * @param array $parameters
	 *
	 * @return void
	 */
	public function cleanUncachedContent(&$parameters) {
		$tsfe = &$parameters['pObj'];
		if ($tsfe instanceof \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController) {
			if ($tsfe->isINTincScript() === true) {
				$this->clean($tsfe);
			}
		}
	}

	/**
	 * Clean cache content from FrontendRenderer
	 * hook is called before Caching!
	 * => for modification of pages on their way in the cache.
	 *
	 * @param array $parameters
	 *
	 * @return void
	 */
	public function cleanCachedContent(&$parameters) {
		$tsfe = &$parameters['pObj'];
		if ($tsfe instanceof \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController) {
			if ($tsfe->isINTincScript() === false) {
				$this->clean($tsfe);
			}
		}
	}

	/**
	 * Initialize needed variables
	 *
	 * @return void
	 *
	 * @throws \TYPO3\CMS\Frontend\Exception
	 */
	protected function initialize() {
		if (!($GLOBALS['TSFE'] instanceof \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController)) {
			throw new \TYPO3\CMS\Frontend\Exception('No frontend class rendered!');
		}
		if ($this->cleanHtmlService === NULL) {
			/** @var CleanHtmlService $cleanHtmlService */
			$this->cleanHtmlService = $this->getInstance('HTML\\Sourceopt\\Service\\CleanHtmlService');
		}
	}

	/**
	 * Create instance when no object manager initiated
	 *
	 * @param string $class
	 *
	 * @return object given class
	 */
	protected function getInstance($class) {
		static $objectManager;
		if (!($objectManager instanceof \TYPO3\CMS\Extbase\Object\ObjectManager)) {
			$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		}

		if ($objectManager instanceof \TYPO3\CMS\Extbase\Object\ObjectManager) {
			return $objectManager->get($class);
		}
		return NULL;
	}
}
