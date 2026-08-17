<?php

namespace App\Tests\Unit;

use App\Services\SegmentationEngine;
use PHPUnit\Framework\TestCase;

class SegmentationEngineTest extends TestCase
{
    public function testSegmentationEngineInstantiates(): void
    {
        $engine = new SegmentationEngine();
        $this->assertInstanceOf(SegmentationEngine::class, $engine);
    }
}
