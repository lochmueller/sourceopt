<?php

declare(strict_types=1);

namespace HTML\Sourceopt\Manipulation;

class RemoveGenerator implements ManipulationInterface
{
    /**
     * @param string $html          The original HTML
     * @param array  $configuration Configuration
     *
     * @return string the manipulated HTML
     */
    public function manipulate(string $html, array $configuration = []): string
    {
        $regex = '<meta name=["\']?generator["\']? [^>]+>';

        return (string) preg_replace('/'.$regex.'/is', '', $html);
    }
}
