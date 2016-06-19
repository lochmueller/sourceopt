<?php
/**
 * RemoveGeneratorTest
 *
 * @author  Tim Lochmüller
 */

namespace HTML\Sourceopt\Tests\Unit\Service;

use HTML\Sourceopt\Manipulation\RemoveGenerator;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * RemoveGeneratorTest
 */
class RemoveGeneratorTest extends UnitTestCase
{

    /**
     * @test
     */
    public function testRemoveGenerator()
    {
        $html = '<head>
<meta name="Regisseur" content="Peter Jackson">
<meta name="generator" content="Tester">
</head>';
        $cleanService = new RemoveGenerator();
        $result = $cleanService->manipulate($html);

        $expected = '<head>
<meta name="Regisseur" content="Peter Jackson">

</head>';
        $this->assertEquals($expected, $result);
    }
}
