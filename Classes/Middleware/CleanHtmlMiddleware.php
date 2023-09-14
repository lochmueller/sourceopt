<?php

declare(strict_types=1);

namespace HTML\Sourceopt\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\NullResponse;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * CleanHtmlMiddleware.
 */
class CleanHtmlMiddleware implements MiddlewareInterface
{
    /**
     * Clean the HTML output.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if (!($response instanceof NullResponse)
        && $GLOBALS['TSFE'] instanceof TypoScriptFrontendController
        && ($GLOBALS['TSFE']->config['config']['sourceopt.']['enabled'] ?? false)
        && 'text/html' == substr($response->getHeaderLine('Content-Type'), 0, 9)
        && !empty($response->getBody())
        ) {
            $processedHtml = GeneralUtility::makeInstance(\HTML\Sourceopt\Service\CleanHtmlService::class)
                ->clean(
                    (string) $response->getBody(),
                    (array) $GLOBALS['TSFE']->config['config']['sourceopt.']
                )
            ;

            // Replace old body with $processedHtml
            $responseBody = new Stream('php://temp', 'rw');
            $responseBody->write($processedHtml);
            $response = $response->withBody($responseBody);
        }

        return $response;
    }
}
