<?php

declare(strict_types=1);
/**
 * Manipulation interface.
 *
 * @author  Tim Lochmüller
 */

namespace HTML\Sourceopt\Manipulation;

/**
 * Manipulation interface.
 */
interface ManipulationInterface
{
    /**
     * @param string $html          The original HTML
     * @param array  $configuration Configuration
     *
     * @return string the manipulated HTML
     */
    public function manipulate(string $html, array $configuration = []): string;
}
