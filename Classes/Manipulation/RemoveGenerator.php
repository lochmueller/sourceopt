<?php declare(strict_types=1);
/**
 * RemoveGenerator.
 *
 * @author  Tim Lochmüller
 */

namespace HTML\Sourceopt\Manipulation;

/**
 * RemoveGenerator.
 */
class RemoveGenerator implements ManipulationInterface
{
    /**
     * @param string $html          The original HTML
     * @param array  $configuration Configuration
     *
     * @return string the manipulated HTML
     */
    public function manipulate($html, array $configuration = [])
    {
        $regex = '<meta name=["\']?generator["\']? [^>]+>';

        return preg_replace('/'.$regex.'/is', '', $html);
    }
}
