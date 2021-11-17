<?php

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
 * SvgStoreMiddleware
 */
class SvgStoreMiddleware implements MiddlewareInterface
{
    /**
     * Search/Extract/Merge SVGs @ HTML output
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if(!($response instanceof NullResponse)
        && $GLOBALS['TSFE'] instanceof TypoScriptFrontendController
        && $GLOBALS['TSFE']->isOutputting()
        && false !== (bool) $GLOBALS['TSFE']->config['config']['svgstore.']['enabled']
        ){
            $processedHtml = GeneralUtility::makeInstance(\HTML\Sourceopt\Service\SvgStoreService::class)
                                                          ->process($response->getBody()->__toString());

            $responseBody = new Stream('php://temp', 'rw');
            $responseBody->write($processedHtml);
            $response = $response->withBody($responseBody);
        }

        return $response;
    }
}