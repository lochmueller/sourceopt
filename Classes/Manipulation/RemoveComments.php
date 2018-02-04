<?php
/**
 * RemoveComments
 *
 * @author  Tim Lochmüller
 */

namespace HTML\Sourceopt\Manipulation;

/**
 * RemoveComments
 */
class RemoveComments implements ManipulationInterface
{

    /**
     * Patterns for white-listing comments inside content
     *
     * @var array
     */
    protected $whiteListCommentsPatterns = [];

    /**
     * @param string $html          The original HTML
     * @param array  $configuration Configuration
     *
     * @return string the manipulated HTML
     */
    public function manipulate($html, array $configuration = [])
    {
        if (isset($configuration['keep.'])) {
            $this->whiteListCommentsPatterns = $configuration['keep.'];
        }

        // match all styles, scripts and comments
        $matches = [];
        preg_match_all(
            '/(?s)((<!--.*?-->)|(<[ \n\r]*style[^>]*>.*?<[ \n\r]*\/style[^>]*>)|(<[ \n\r]*script[^>]*>.*?<[ \n\r]*\/script[^>]*>))/im',
            $html,
            $matches
        );
        foreach ($matches[0] as $tag) {
            if ($this->keepComment($tag) === false) {
                $html = str_replace($tag, '', $html);
            }
        }
        return $html;
    }

    /**
     * Check if a comment is defined to be kept in a pattern whiteListOfComments
     *
     * @param string $commentHtml
     *
     * @return boolean
     */
    protected function keepComment($commentHtml)
    {
        // if not even a comment, skip this
        if (!preg_match('/^\<\!\-\-(.*?)\-\-\>$/usi', $commentHtml)) {
            return true;
        }

        // if not defined in white list
        if (!empty($this->whiteListCommentsPatterns)) {
            $commentHtml = str_replace("<!--", "", $commentHtml);
            $commentHtml = str_replace("-->", "", $commentHtml);
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
