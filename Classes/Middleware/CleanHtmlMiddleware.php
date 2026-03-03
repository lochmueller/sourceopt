<?php

declare(strict_types=1);

namespace HTML\Sourceopt\Middleware;

use HTML\Sourceopt\Service\CleanHtmlService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CleanHtmlMiddleware extends AbstractMiddleware
{
    /**
     * Clean the HTML output.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if ($this->responseIsAlterable($response) && ($GLOBALS['TSFE']->config['config']['sourceopt.']['enabled'] ?? false)) {
            $cleanHtmlService = GeneralUtility::makeInstance(CleanHtmlService::class);
            $processedHtml = $cleanHtmlService->clean(
                (string) $response->getBody(),
                (array) $GLOBALS['TSFE']->config['config']['sourceopt.']
            );
            $response = $response->withBody($this->getStringStream($processedHtml));
        }

        return $response;
    }
}
