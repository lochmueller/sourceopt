<?php

namespace HTML\Sourceopt\Middleware;

use HTML\Sourceopt\Service\CleanHtmlService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\NullResponse;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * CleanHtmlMiddleware
 */
class CleanHtmlMiddleware implements MiddlewareInterface
{
    /**
     * @var CleanHtmlService
     */
    protected $cleanHtmlService = null;

    public function __construct()
    {
        $this->cleanHtmlService = GeneralUtility::makeInstance(CleanHtmlService::class);
    }

    /**
     * Clean the HTML output
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if (!($response instanceof NullResponse)
            && $GLOBALS['TSFE'] instanceof TypoScriptFrontendController) {
            $processedHtml = $this->cleanHtmlService->clean(
                $response->getBody()->__toString(),
                $GLOBALS['TSFE']->config['config']['sourceopt.']
            );

            // Replace old body with $processedHtml
            $responseBody = new Stream('php://temp', 'rw');
            $responseBody->write($processedHtml);
            $response = $response->withBody($responseBody);
        }

        return $response;
    }
}
