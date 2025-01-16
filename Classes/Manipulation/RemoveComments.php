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

        // match all comments, styles and scripts
        $matches = [];
        preg_match_all(
            '/(?s)((<!--.*?-->)|(<[ \n\r]*style[^>]*>.*?<[ \n\r]*\/style[^>]*>)|(<[ \n\r]*script[^>]*>.*?<[ \n\r]*\/script[^>]*>))/im',
            $html,
            $matches
        );
        foreach ($matches[0] as $tag) {
            if (false === $this->keepComment($tag)) {
                $html = str_replace($tag, '', $html);
            }
        }

        return $html;
    }

    /**
     * Check if a comment is defined to be kept in a pattern whiteListOfComments.
     */
    protected function keepComment(string $commentHtml): bool
    {
        // if not even a comment, skip this
        if (!preg_match('/^\<\!\-\-(.*?)\-\-\>$/usi', $commentHtml)) {
            return true;
        }

        // if not defined in white list
        if (!empty($this->whiteListCommentsPatterns)) {
            $commentHtml = str_replace('<!--', '', $commentHtml);
            $commentHtml = str_replace('-->', '', $commentHtml);
            $commentHtml = trim($commentHtml);
            foreach ($this->whiteListCommentsPatterns as $pattern) {
                if (!empty($pattern) && preg_match($pattern, $commentHtml)) {
                    return true;
                }
            }
        }

        return false;
    }
}
