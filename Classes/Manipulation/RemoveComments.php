<?php

declare(strict_types=1);

namespace HTML\Sourceopt\Manipulation;

class RemoveComments implements ManipulationInterface
{
    /**
     * Patterns for white-listing comments inside content.
     */
    protected array $whiteListCommentsPatterns = [];

    /**
     * @param string $html          The original HTML
     * @param array  $configuration Configuration
     *
     * @return string the manipulated HTML
     */
    public function manipulate(string $html, array $configuration = []): string
    {
        if (isset($configuration['keep.'])) {// DEPRECATED
            $this->whiteListCommentsPatterns = $configuration['keep.'];
        }

        return preg_replace_callback(
            '/\s*<!--([\s\S]*?)-->/',
            function (array $match): string {
                if ($this->keepComment($match[1])) {
                    return $match[0];
                }
                return '';
            },
            $html
        );

        return $html;
    }

    /**
     * Check if a comment is defined to be kept in a pattern whiteListOfComments.
     */
    protected function keepComment(string $comment): bool
    {
        // if not defined in white list
        if (!empty($this->whiteListCommentsPatterns)) {
            $comment = trim($comment);
            foreach ($this->whiteListCommentsPatterns as $pattern) {
                if (preg_match($pattern, $comment)) {
                    return true;
                }
            }
        }
        return false;
    }
}
