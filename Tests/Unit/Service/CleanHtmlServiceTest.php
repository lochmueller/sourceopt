<?php

namespace HTML\Sourceopt\Service;

use HTML\Sourceopt\Manipulation\ManipulationInterface;
use HTML\Sourceopt\Manipulation\RemoveBlurScript;
use HTML\Sourceopt\Manipulation\RemoveComments;
use HTML\Sourceopt\Manipulation\RemoveGenerator;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;


class CleanHtmlServiceTest extends \PHPUnit\Framework\TestCase
{
    public function testFormatHtml()
    {
        $clean = new CleanHtmlService();
        $config = [
            'enabled' => true,
            'removeComments' => true,
            'formatHtml' => 4,
            'formatHtml.' => [
                'tabSize' => 2,
            ],
        ];

        $svg =
'<svg>
  <path/>
  <path/>
  <path>
    <path/>
    <path>
      <path>
        <path></path>
      </path>
    </path>
  </path>
</svg>';
        $result = $clean->clean($svg, $config);
        $this->assertEquals($svg, $result);
    }
}