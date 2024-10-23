<?php

declare(strict_types=1);

namespace HTML\Sourceopt\Middleware;

use HTML\Sourceopt\Service\SvgStoreService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SvgStoreMiddleware extends AbstractMiddleware
{
    public function __construct(protected SvgStoreService $svgStoreService) {}

    /**
     * Search/Extract/Merge SVGs @ HTML output.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if ($this->responseIsAlterable($response) && $GLOBALS['TSFE']->config['config']['svgstore.']['enabled'] ?? false) {
            $processedHtml = $this->svgStoreService->process((string) $response->getBody());
            $response = $response->withBody($this->getStringStream($processedHtml));
        }

        return $response;
    }
}
