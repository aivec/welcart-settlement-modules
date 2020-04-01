<?php
use PHPUnit\Framework\TestCase;
use Aivec\Welcart\SettlementModules\Module;
use InvalidArgumentException;

require 'mocks.php';

class ModuleArgsTest extends TestCase
{
    public function testValidateDivisionsWillThrowExceptionOnNotArray()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('valid_divisions must be an array');

        $divisions = 1;
        new Module('test', 'test', 'test', 'test', $divisions);
    }

    public function testValidateDivisionsWillThrowExceptionOnNoDivisionsGiven()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("valid_divisions must contain at least one of 'shipped', 'service', or 'data'");

        $divisions = ['random'];
        new Module('test', 'test', 'test', 'test', $divisions);
    }

    public function testValidateDivisionsWillThrowExceptionOnPaymentTypeNotArray()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('charge types must be an array');

        $divisions = ['shipped' => 1];
        new Module('test', 'test', 'test', 'test', $divisions);
    }

    public function testValidateDivisionsWillThrowExceptionOnInvalidPaymentType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("division 'shipped' does not contain any valid charge types");

        $divisions = ['shipped' => ['random']];
        new Module('test', 'test', 'test', 'test', $divisions);
    }
}
