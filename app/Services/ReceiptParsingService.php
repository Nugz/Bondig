<?php

namespace App\Services;

use App\DTOs\ParsedBonus;
use App\DTOs\ParsedLine;
use App\DTOs\ParseResult;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Spatie\PdfToText\Pdf;

class ReceiptParsingService
{
    public function parseFromPdf(UploadedFile $file): ParseResult
    {
        try {
            $rawText = $this->extractText($file->path());

            if (empty($rawText)) {
                return new ParseResult(
                    success: false,
                    lines: [],
                    total: null,
                    purchasedAt: null,
                    rawText: null,
                    errors: ['Could not extract text from PDF']
                );
            }

            $lines = $this->parseLines($rawText);
            $total = $this->extractTotal($rawText);
            $purchasedAt = $this->extractDate($rawText);
            $bonuses = $this->extractBonusSection($rawText);

            if (empty($lines)) {
                return new ParseResult(
                    success: false,
                    lines: [],
                    total: $total,
                    purchasedAt: $purchasedAt,
                    rawText: $rawText,
                    errors: ['No product lines found in receipt'],
                    bonuses: $bonuses
                );
            }

            return new ParseResult(
                success: true,
                lines: $lines,
                total: $total,
                purchasedAt: $purchasedAt,
                rawText: $rawText,
                errors: [],
                bonuses: $bonuses
            );
        } catch (\Exception $e) {
            Log::error('Receipt parsing failed', [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
            ]);

            return new ParseResult(
                success: false,
                lines: [],
                total: null,
                purchasedAt: null,
                rawText: null,
                errors: [$e->getMessage()]
            );
        }
    }

    protected function extractText(string $path): string
    {
        // Use -layout option to preserve the columnar format of AH receipts
        return Pdf::getText($path, null, ['layout']);
    }

    protected function parseLines(string $rawText): array
    {
        $lines = [];
        $textLines = explode("\n", $rawText);

        foreach ($textLines as $i => $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $parsedLine = $this->parseLine($line, $textLines, $i);
            if ($parsedLine !== null) {
                $lines[] = $parsedLine;
            }
        }

        return $lines;
    }

    protected function parseLine(string $line, array $allLines, int $index): ?ParsedLine
    {
        // Remove control characters (form feed, etc.) that may appear in PDF text
        $line = preg_replace('/[\x00-\x1F\x7F]/', '', $line);
        $line = trim($line);

        // Skip non-product lines early
        if ($this->isNonProductLine($line)) {
            return null;
        }

        // AH Format 1: Qty at start with unit price and total
        // "4        PAPRIKA              0,89     3,56 B"
        // "2        AH EMMENTALE         2,79     5,58 B"
        if (preg_match('/^(\d+)\s+(.+?)\s+(\d+[,.]\d{2})\s+(\d+[,.]\d{2})\s*(\d*%?|B)?$/u', $line, $matches)) {
            // Products with B marker or percentage discount (35%) are bonus/discount items
            $hasDiscount = !empty($matches[5]) && (str_contains($matches[5], 'B') || str_contains($matches[5], '%'));
            return new ParsedLine(
                name: trim($matches[2]),
                quantity: (int)$matches[1],
                unitPrice: $this->parsePrice($matches[3]),
                totalPrice: $this->parsePrice($matches[4]),
                isBonus: $hasDiscount,
                rawText: $line
            );
        }

        // AH Format 2: Qty at start with only total price
        // "1        WINTERPEEN                    0,35"
        // "1        BEEMSTER                    10,27 35%"
        if (preg_match('/^(\d+)\s+(.+?)\s+(\d+[,.]\d{2})\s*(\d*%|B)?$/u', $line, $matches)) {
            $name = trim($matches[2]);
            // Skip if name looks like a non-product
            if ($this->isNonProductLine($name)) {
                return null;
            }

            // Products with B marker or percentage discount (35%) are bonus/discount items
            $hasDiscount = !empty($matches[4]) && (str_contains($matches[4], 'B') || str_contains($matches[4], '%'));
            $totalPrice = $this->parsePrice($matches[3]);
            $quantity = (int)$matches[1];
            $unitPrice = $quantity > 0 ? $totalPrice / $quantity : $totalPrice;

            return new ParsedLine(
                name: $name,
                quantity: $quantity,
                unitPrice: round($unitPrice, 2),
                totalPrice: $totalPrice,
                isBonus: $hasDiscount,
                rawText: $line
            );
        }

        return null;
    }

    protected function isNonProductLine(string $name): bool
    {
        // Keywords that should match as substrings (safe, unlikely to appear in product names)
        $substringKeywords = [
            'subtotaal',
            'totaal',
            'te betalen',
            'statiegeld',
            '+statiegeld',
            'bonuskaart',
            'airmiles',
            'koopzegels',
            'spaaracties',
            'espaarzegelspremium',
            'uw voordeel',
            'omschrijving',
        ];

        // Keywords that must match as whole words (could appear inside product names)
        // e.g., "pin" in "CAMPINA", "btw" in product codes
        $wholeWordKeywords = [
            'btw',
            'pin',
            'pinnen',
            'contant',
            'retour',
            'korting',
            'bonus',
            'actie',
            'betaald',
            'waarvan',
            'aantal',
            'prijs',
            'bedrag',
        ];

        $lowerName = strtolower(trim($name));

        // Skip lines starting with + (deposits)
        if (str_starts_with($lowerName, '+')) {
            return true;
        }

        // Check substring keywords
        foreach ($substringKeywords as $keyword) {
            if (str_contains($lowerName, $keyword)) {
                return true;
            }
        }

        // Check whole word keywords using word boundaries
        foreach ($wholeWordKeywords as $keyword) {
            if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/', $lowerName)) {
                return true;
            }
        }

        return false;
    }

    protected function extractTotal(string $rawText): ?float
    {
        // Look for standalone "TOTAAL" line followed by amount (AH format with lots of whitespace)
        // "TOTAAL                                                                    144,81"
        if (preg_match('/^TOTAAL\s+(\d+[,.]\d{2})\s*$/mu', $rawText, $matches)) {
            return $this->parsePrice($matches[1]);
        }

        // Look for "TOTAAL" or "TE BETALEN" followed by amount (general format)
        // Using \x{20AC} for euro sign in UTF-8 mode
        if (preg_match('/(?:TOTAAL|TE BETALEN)\s+(?:\x{20AC})?\s*(\d+[,.]\d+|\d+)/iu', $rawText, $matches)) {
            return $this->parsePrice($matches[1]);
        }

        return null;
    }

    protected function extractDate(string $rawText): ?Carbon
    {
        // Pattern 1: DD/MM/YYYY HH:MM (payment section format)
        if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})\s+(\d{2}):(\d{2})/m', $rawText, $matches)) {
            try {
                return Carbon::createFromFormat(
                    'd-m-Y H:i',
                    sprintf('%s-%s-%s %s:%s', $matches[1], $matches[2], $matches[3], $matches[4], $matches[5]),
                    'Europe/Amsterdam'
                );
            } catch (\Exception $e) {
                Log::warning('Could not parse date from receipt', [
                    'match' => $matches[0],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Pattern 2: DD-MM-YYYY HH:MM (alternative format)
        if (preg_match('/(\d{2})-(\d{2})-(\d{4})\s+(\d{2}):(\d{2})/m', $rawText, $matches)) {
            try {
                return Carbon::createFromFormat(
                    'd-m-Y H:i',
                    sprintf('%s-%s-%s %s:%s', $matches[1], $matches[2], $matches[3], $matches[4], $matches[5]),
                    'Europe/Amsterdam'
                );
            } catch (\Exception $e) {
                Log::warning('Could not parse date from receipt', [
                    'match' => $matches[0],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Pattern 3: DD-MM-YYYY (date only, at bottom of receipt)
        if (preg_match('/(\d{2})-(\d{2})-(\d{4})(?!\s*\d)/m', $rawText, $matches)) {
            try {
                return Carbon::createFromFormat(
                    'd-m-Y',
                    sprintf('%s-%s-%s', $matches[1], $matches[2], $matches[3]),
                    'Europe/Amsterdam'
                )->startOfDay();
            } catch (\Exception $e) {
                Log::warning('Could not parse date from receipt', [
                    'match' => $matches[0],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return null;
    }

    protected function parsePrice(string $price): float
    {
        // Replace comma with dot for decimal separator
        $price = str_replace(',', '.', $price);
        return (float)$price;
    }

    /**
     * Extract bonus section from receipt text.
     * The bonus section appears after the first "Subtotaal" (with total amount) and before "TOTAAL".
     *
     * AH receipt format has:
     * - "46                     SUBTOTAAL                                            145,99" (first subtotal)
     * - BONUS lines with discounts
     * - "UW VOORDEEL" section
     * - Possibly a second "SUBTOTAAL" inside the voordeel breakdown
     * - "TOTAAL" with final amount
     *
     * @return ParsedBonus[]
     */
    protected function extractBonusSection(string $rawText): array
    {
        $bonuses = [];
        $textLines = explode("\n", $rawText);
        $inBonusSection = false;

        foreach ($textLines as $line) {
            $trimmedLine = trim($line);
            if (empty($trimmedLine)) {
                continue;
            }

            // Start looking for bonuses after SUBTOTAAL with amount (the main subtotal line)
            // This handles "46  SUBTOTAAL  145,99" format where there's a count prefix
            if (!$inBonusSection && preg_match('/SUBTOTAAL\s+\d+[,.]\d{2}/i', $trimmedLine)) {
                $inBonusSection = true;
                continue;
            }

            // Stop at standalone "TOTAAL" line (the final total)
            if ($inBonusSection && preg_match('/^TOTAAL\s+\d+[,.]\d{2}/i', $trimmedLine)) {
                break;
            }

            // Also stop if we hit "UW VOORDEEL" - bonuses are listed before this summary
            if ($inBonusSection && preg_match('/^UW VOORDEEL/i', $trimmedLine)) {
                break;
            }

            if ($inBonusSection) {
                $bonus = $this->parseBonusLine($trimmedLine);
                if ($bonus !== null) {
                    $bonuses[] = $bonus;
                }
            }
        }

        return $bonuses;
    }

    /**
     * Parse a single bonus line.
     * Formats:
     * - "BONUS                  AHPAPRIKAROO                                           -0,58"
     * - "35% K                  BEEMSTER                                               -3,59"
     */
    protected function parseBonusLine(string $line): ?ParsedBonus
    {
        // Pattern 1: BONUS followed by product name and negative amount
        if (preg_match('/^BONUS\s+(.+?)\s+(-?\d+[,.]\d{2})\s*$/i', $line, $matches)) {
            $rawName = trim($matches[1]);
            $amount = $this->parsePrice($matches[2]);

            return new ParsedBonus(
                rawName: $rawName,
                discountAmount: abs($amount)
            );
        }

        // Pattern 2: Percentage discount (e.g., "35% K  BEEMSTER  -3,59")
        if (preg_match('/^\d+%\s*\w?\s+(.+?)\s+(-?\d+[,.]\d{2})\s*$/i', $line, $matches)) {
            $rawName = trim($matches[1]);
            $amount = $this->parsePrice($matches[2]);

            return new ParsedBonus(
                rawName: $rawName,
                discountAmount: abs($amount)
            );
        }

        return null;
    }
}
