<?php

declare(strict_types=1);

namespace HTML\Sourceopt\Tests\Unit\Service;

use HTML\Sourceopt\Manipulation\RemoveComments;
use HTML\Sourceopt\Tests\Unit\AbstractUnitTest;

/**
 * @internal
 *
 * @coversNothing
 */
class RemoveCommentTest extends AbstractUnitTest
{
    /**
     * @dataProvider generatorProvider
     */
    public function testRemoveComment($before, $after): void
    {
        $cleanService = new RemoveComments();
        $result = $cleanService->manipulate($before);

        $this->assertEquals($after, $result);
    }

    public static function generatorProvider(): array
    {
        return [
            [
                '<head>
<meta name="Regisseur" content="Peter Jackson">
<meta name="generator" content="Tester">
<!-- Ich bin ein Test -->
</head>',
                '<head>
<meta name="Regisseur" content="Peter Jackson">
<meta name="generator" content="Tester">

</head>',
            ],
            [
                '<head>
<!-- Ich bin ein Test -->
<meta name="Regisseur" content="Peter Jackson">
<meta name="generator" content="Tester">
<!-- Ich bin ein Test -->
</head>',
                '<head>

<meta name="Regisseur" content="Peter Jackson">
<meta name="generator" content="Tester">

</head>',
            ],
        ];
    }
}
