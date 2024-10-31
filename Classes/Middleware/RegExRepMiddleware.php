<?php

declare(strict_types=1);

namespace HTML\Sourceopt\Middleware;

use HTML\Sourceopt\Service\RegExRepService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RegExRepMiddleware extends AbstractMiddleware
{
    public function __construct(protected RegExRepService $regExRepService) {}

    /**
     * RegEx search & replace @ HTML output.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if ($this->responseIsAlterable($response) && ($GLOBALS['TSFE']->config['config']['replacer.'] ?? false)) {
            $processedHtml = $this->regExRepService->process((string) $response->getBody());
            $response = $response->withBody($this->getStringStream($processedHtml));
        }

        return $response;
    }
}
