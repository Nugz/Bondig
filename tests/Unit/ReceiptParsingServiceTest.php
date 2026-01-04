<?php

namespace Tests\Unit;

use App\DTOs\ParsedBonus;
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

    public function test_extract_bonus_section_finds_bonus_lines_after_subtotal(): void
    {
        $service = new class extends ReceiptParsingService {
            public function testExtractBonusSection(string $text): array
            {
                return $this->extractBonusSection($text);
            }
        };

        $text = "1        PAPRIKA              0,89     3,56 B" . PHP_EOL .
                "Subtotaal                               10,00" . PHP_EOL .
                "BONUS                  AHPAPRIKAROO                                           -0,58" . PHP_EOL .
                "TOTAAL                                   9,42";

        $bonuses = $service->testExtractBonusSection($text);

        $this->assertCount(1, $bonuses);
        $this->assertInstanceOf(ParsedBonus::class, $bonuses[0]);
        $this->assertEquals('AHPAPRIKAROO', $bonuses[0]->rawName);
        $this->assertEquals(0.58, $bonuses[0]->discountAmount);
    }

    public function test_extract_bonus_section_handles_multiple_bonuses(): void
    {
        $service = new class extends ReceiptParsingService {
            public function testExtractBonusSection(string $text): array
            {
                return $this->extractBonusSection($text);
            }
        };

        $text = "Products..." . PHP_EOL .
                "Subtotaal                               50,00" . PHP_EOL .
                "BONUS                  AHPAPRIKAROO                                           -0,58" . PHP_EOL .
                "BONUS                  AHKOMKOM500G                                           -1,20" . PHP_EOL .
                "BONUS                  BEEMSTER                                               -2,50" . PHP_EOL .
                "TOTAAL                                  45,72";

        $bonuses = $service->testExtractBonusSection($text);

        $this->assertCount(3, $bonuses);
        $this->assertEquals('AHPAPRIKAROO', $bonuses[0]->rawName);
        $this->assertEquals(0.58, $bonuses[0]->discountAmount);
        $this->assertEquals('AHKOMKOM500G', $bonuses[1]->rawName);
        $this->assertEquals(1.20, $bonuses[1]->discountAmount);
        $this->assertEquals('BEEMSTER', $bonuses[2]->rawName);
        $this->assertEquals(2.50, $bonuses[2]->discountAmount);
    }

    public function test_extract_bonus_section_returns_empty_when_no_subtotal(): void
    {
        $service = new class extends ReceiptParsingService {
            public function testExtractBonusSection(string $text): array
            {
                return $this->extractBonusSection($text);
            }
        };

        $text = "1        PAPRIKA              0,89     3,56 B" . PHP_EOL .
                "TOTAAL                                   3,56";

        $bonuses = $service->testExtractBonusSection($text);

        $this->assertEmpty($bonuses);
    }

    public function test_parse_bonus_line_extracts_name_and_amount(): void
    {
        $service = new class extends ReceiptParsingService {
            public function testParseBonusLine(string $line): ?ParsedBonus
            {
                return $this->parseBonusLine($line);
            }
        };

        $line = "BONUS                  AHPAPRIKAROO                                           -0,58";
        $bonus = $service->testParseBonusLine($line);

        $this->assertNotNull($bonus);
        $this->assertEquals('AHPAPRIKAROO', $bonus->rawName);
        $this->assertEquals(0.58, $bonus->discountAmount);
    }

    public function test_parse_bonus_line_handles_positive_amount(): void
    {
        $service = new class extends ReceiptParsingService {
            public function testParseBonusLine(string $line): ?ParsedBonus
            {
                return $this->parseBonusLine($line);
            }
        };

        // Some receipts might not have the minus sign
        $line = "BONUS                  AHPAPRIKAROO                                           0,58";
        $bonus = $service->testParseBonusLine($line);

        $this->assertNotNull($bonus);
        $this->assertEquals(0.58, $bonus->discountAmount);
    }

    public function test_parse_bonus_line_returns_null_for_non_bonus_line(): void
    {
        $service = new class extends ReceiptParsingService {
            public function testParseBonusLine(string $line): ?ParsedBonus
            {
                return $this->parseBonusLine($line);
            }
        };

        $this->assertNull($service->testParseBonusLine("1        PAPRIKA              0,89     3,56 B"));
        $this->assertNull($service->testParseBonusLine("TOTAAL                                  45,72"));
        $this->assertNull($service->testParseBonusLine("Some random text"));
    }

    public function test_parse_bonus_line_handles_percentage_discount(): void
    {
        $service = new class extends ReceiptParsingService {
            public function testParseBonusLine(string $line): ?ParsedBonus
            {
                return $this->parseBonusLine($line);
            }
        };

        // Percentage discounts like "35% K  BEEMSTER  -3,59"
        $line = "35% K                  BEEMSTER                                               -3,59";
        $bonus = $service->testParseBonusLine($line);

        $this->assertNotNull($bonus);
        $this->assertEquals('BEEMSTER', $bonus->rawName);
        $this->assertEquals(3.59, $bonus->discountAmount);
    }

    public function test_extract_bonus_section_handles_subtotal_with_item_count(): void
    {
        $service = new class extends ReceiptParsingService {
            public function testExtractBonusSection(string $text): array
            {
                return $this->extractBonusSection($text);
            }
        };

        // Real AH format: "46  SUBTOTAAL  145,99" with item count prefix
        $text = "1        PAPRIKA              0,89     3,56 B" . PHP_EOL .
                "46                     SUBTOTAAL                                            145,99" . PHP_EOL .
                "BONUS                  AHPAPRIKAROO                                           -0,58" . PHP_EOL .
                "BONUS                  LAYS,CHEETOS                                           -1,31" . PHP_EOL .
                "35% K                  BEEMSTER                                               -3,59" . PHP_EOL .
                "UW VOORDEEL                                                                 25,18" . PHP_EOL .
                "TOTAAL                                                                    144,81";

        $bonuses = $service->testExtractBonusSection($text);

        $this->assertCount(3, $bonuses);
        $this->assertEquals('AHPAPRIKAROO', $bonuses[0]->rawName);
        $this->assertEquals(0.58, $bonuses[0]->discountAmount);
        $this->assertEquals('LAYS,CHEETOS', $bonuses[1]->rawName);
        $this->assertEquals(1.31, $bonuses[1]->discountAmount);
        $this->assertEquals('BEEMSTER', $bonuses[2]->rawName);
        $this->assertEquals(3.59, $bonuses[2]->discountAmount);
    }
}
