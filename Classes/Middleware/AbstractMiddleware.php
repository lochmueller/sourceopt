<?php

declare(strict_types=1);

namespace HTML\Sourceopt\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\MiddlewareInterface;
use TYPO3\CMS\Core\Http\NullResponse;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

abstract class AbstractMiddleware implements MiddlewareInterface
{
    protected function responseIsAlterable(ResponseInterface $response): bool
    {
        if (!$response instanceof NullResponse) {
            return false;
        }

        if (!$GLOBALS['TSFE'] instanceof TypoScriptFrontendController) {  // need for configuration
            return false;
        }

        if ('text/html' !== substr($response->getHeaderLine('Content-Type'), 0, 9)) {
            return false;
        }

        if (empty($response->getBody())) {
            return false;
        }

        return true;
    }

    protected function getStringStream(string $content): StreamInterface
    {
        $body = new Stream('php://temp', 'rw');
        $body->write($content);

        return $body;
    }
}
