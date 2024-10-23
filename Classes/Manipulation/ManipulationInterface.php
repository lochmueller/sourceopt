<?php

declare(strict_types=1);

namespace HTML\Sourceopt\Manipulation;

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
