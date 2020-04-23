<?php
use PHPUnit\Framework\TestCase;
use Aivec\Welcart\SettlementModules\Module;
use Aivec\Welcart\SettlementModules\Currency;

require_once 'mocks.php';

class CurrenciesTest extends TestCase
{
    protected $module;

    public function setUp(): void
    {
        $this->module = new Module('test', 'test', 'test');
    }

    public function testValidateCurrencyModelWillThrowExceptionOnUnrecognizedCurrencyCode()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("MMM is not a valid currency. The format is not ISO 4217, or Welcart doesn't support it");

        $valid_currencies = ['MMM'];
        new Currency($this->module, $valid_currencies);
    }

    public function testValidateCurrencyModelWillThrowExceptionOnLowerCaseCurrencyCode()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("jpy is not a valid currency. The format is not ISO 4217, or Welcart doesn't support it");

        $valid_currencies = ['jpy'];
        new Currency($this->module, $valid_currencies);
    }
}