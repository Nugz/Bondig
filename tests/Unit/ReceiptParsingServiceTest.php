<?php

namespace Tests\Unit;

use App\DTOs\ParseResult;
use App\Services\ReceiptParsingService;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class ReceiptParsingServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_parse_result_is_returned_with_success_false_when_no_text_extracted(): void
    {
        $service = Mockery::mock(ReceiptParsingService::class)->makePartial();
        $service->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('extractText')->andReturn('');

        $file = UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf');
        $result = $service->parseFromPdf($file);

        $this->assertInstanceOf(ParseResult::class, $result);
        $this->assertFalse($result->success);
        $this->assertContains('Could not extract text from PDF', $result->errors);
    }

    public function test_extract_total_finds_totaal_amount(): void
    {
        $service = new class extends ReceiptParsingService {
            public function testExtractTotal(string $text): ?float
            {
                return $this->extractTotal($text);
            }
        };

        $text = "Some products" . PHP_EOL . "TOTAAL 45.67" . PHP_EOL . "Payment info";
        $this->assertEquals(45.67, $service->testExtractTotal($text));
    }

    public function test_extract_total_finds_te_betalen_amount(): void
    {
        $service = new class extends ReceiptParsingService {
            public function testExtractTotal(string $text): ?float
            {
                return $this->extractTotal($text);
            }
        };

        $text = "Some products" . PHP_EOL . "TE BETALEN 123,45" . PHP_EOL . "Payment info";
        $this->assertEquals(123.45, $service->testExtractTotal($text));
    }

    public function test_extract_date_parses_dutch_date_format(): void
    {
        $service = new class extends ReceiptParsingService {
            public function testExtractDate(string $text): ?\Carbon\Carbon
            {
                return $this->extractDate($text);
            }
        };

        $text = "Store info" . PHP_EOL . "29-12-2025 11:56" . PHP_EOL . "Products...";
        $date = $service->testExtractDate($text);

        $this->assertNotNull($date);
        $this->assertEquals(29, $date->day);
        $this->assertEquals(12, $date->month);
        $this->assertEquals(2025, $date->year);
        $this->assertEquals(11, $date->hour);
        $this->assertEquals(56, $date->minute);
    }

    public function test_parse_price_handles_comma_decimal_separator(): void
    {
        $service = new class extends ReceiptParsingService {
            public function testParsePrice(string $price): float
            {
                return $this->parsePrice($price);
            }
        };

        $this->assertEquals(12.99, $service->testParsePrice('12,99'));
    }

    public function test_parse_price_handles_dot_decimal_separator(): void
    {
        $service = new class extends ReceiptParsingService {
            public function testParsePrice(string $price): float
            {
                return $this->parsePrice($price);
            }
        };

        $this->assertEquals(12.99, $service->testParsePrice('12.99'));
    }

    public function test_is_non_product_line_identifies_total_lines(): void
    {
        $service = new class extends ReceiptParsingService {
            public function testIsNonProductLine(string $name): bool
            {
                return $this->isNonProductLine($name);
            }
        };

        $this->assertTrue($service->testIsNonProductLine('TOTAAL'));
        $this->assertTrue($service->testIsNonProductLine('Subtotaal'));
        $this->assertTrue($service->testIsNonProductLine('TE BETALEN'));
        $this->assertTrue($service->testIsNonProductLine('BTW laag'));
        $this->assertTrue($service->testIsNonProductLine('PIN'));
        $this->assertFalse($service->testIsNonProductLine('AH Melk Halfvol'));
    }

    public function test_extract_date_parses_dd_slash_mm_slash_yyyy_format(): void
    {
        $service = new class extends ReceiptParsingService {
            public function testExtractDate(string $text): ?\Carbon\Carbon
            {
                return $this->extractDate($text);
            }
        };

        $text = "Payment section" . PHP_EOL . "PIN 02/01/2026 14:30" . PHP_EOL . "Amount: 50.00";
        $date = $service->testExtractDate($text);

        $this->assertNotNull($date);
        $this->assertEquals(2, $date->day);
        $this->assertEquals(1, $date->month);
        $this->assertEquals(2026, $date->year);
        $this->assertEquals(14, $date->hour);
        $this->assertEquals(30, $date->minute);
    }

    public function test_extract_date_parses_dd_mm_yyyy_date_only(): void
    {
        $service = new class extends ReceiptParsingService {
            public function testExtractDate(string $text): ?\Carbon\Carbon
            {
                return $this->extractDate($text);
            }
        };

        $text = "Store info" . PHP_EOL . "Receipt date: 15-06-2025" . PHP_EOL . "Products...";
        $date = $service->testExtractDate($text);

        $this->assertNotNull($date);
        $this->assertEquals(15, $date->day);
        $this->assertEquals(6, $date->month);
        $this->assertEquals(2025, $date->year);
        $this->assertEquals(0, $date->hour);
        $this->assertEquals(0, $date->minute);
    }

    public function test_parse_lines_returns_empty_array_for_no_product_lines(): void
    {
        $service = new class extends ReceiptParsingService {
            public function testParseLines(string $text): array
            {
                return $this->parseLines($text);
            }
        };

        $text = "TOTAAL 50.00" . PHP_EOL . "BTW laag 5.00" . PHP_EOL . "PIN" . PHP_EOL . "";
        $lines = $service->testParseLines($text);

        $this->assertIsArray($lines);
        $this->assertEmpty($lines);
    }
}
