<?php

namespace App\Tests\Unit;

use App\Service\UnitConverter;
use PHPUnit\Framework\TestCase;

class UnitConverterTest extends TestCase
{
    private UnitConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new UnitConverter();
    }

    public function testMassConversions(): void
    {
        $this->assertSame(1.5, $this->converter->convert(1500.0, 'g', 'kg'));
        $this->assertSame(250.0, $this->converter->convert(0.25, 'kg', 'g'));
        $this->assertSame(5.0, $this->converter->convert(5.0, 'kg', 'kg'));
    }

    public function testVolumeConversions(): void
    {
        $this->assertSame(0.25, $this->converter->convert(250.0, 'mL', 'L'));
        $this->assertSame(750.0, $this->converter->convert(0.75, 'L', 'mL'));
    }

    public function testPieceConversion(): void
    {
        $this->assertSame(4.0, $this->converter->convert(4.0, 'piece', 'piece'));
    }

    public function testIncompatibleUnitsThrowException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->converter->convert(100.0, 'g', 'L');
    }
}
