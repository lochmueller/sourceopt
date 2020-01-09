<?php
/**
 * RemoveGeneratorTest
 *
 * @author  Tim Lochmüller
 */

namespace HTML\Sourceopt\Tests\Unit\Service;

use HTML\Sourceopt\Manipulation\RemoveGenerator;

/**
 * RemoveGeneratorTest
 */
class RemoveGeneratorTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @test
     * @dataProvider generatorProvider
     */
    public function testRemoveGenerator($before, $after)
    {
        $cleanService = new RemoveGenerator();
        $result = $cleanService->manipulate($before);

        $this->assertEquals($after, $result);
    }

    public function generatorProvider(): array
    {
        return [
            [
                '<head>
<meta name="Regisseur" content="Peter Jackson">
<meta name="generator" content="Tester">
</head>',
                '<head>
<meta name="Regisseur" content="Peter Jackson">

</head>',
            ],
            [
                '<head>
<meta name="Regisseur" content="Peter Jackson">
<meta name="generator" content="TYPO3 CMS" />
</head>',
                '<head>
<meta name="Regisseur" content="Peter Jackson">

</head>',
            ],
            [
                '<head>
<meta name="Regisseur" content="Peter Jackson">
<meta name="other" content="generator" />
</head>',
                '<head>
<meta name="Regisseur" content="Peter Jackson">
<meta name="other" content="generator" />
</head>',
            ],
        ];


    }
}
