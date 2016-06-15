<?php
/**
 * CleanHtmlServiceTest
 *
 * @author  Tim Lochmüller
 */

namespace HTML\Sourceopt\Tests\Unit\Service;

use HTML\Sourceopt\Service\CleanHtmlService;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * CleanHtmlServiceTest
 */
class CleanHtmlServiceTest extends UnitTestCase
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
        $cleanService = new CleanHtmlService();
        $cleanService->removeGenerator($html);

        $expected = '<head> 
<meta name="Regisseur" content="Peter Jackson"> 

</head>';
        $this->assertEquals($expected, $html);
    }
}
