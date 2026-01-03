<?php

namespace App\Services;

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

            if (empty($lines)) {
                return new ParseResult(
                    success: false,
                    lines: [],
                    total: $total,
                    purchasedAt: $purchasedAt,
                    rawText: $rawText,
                    errors: ['No product lines found in receipt']
                );
            }

            return new ParseResult(
                success: true,
                lines: $lines,
                total: $total,
                purchasedAt: $purchasedAt,
                rawText: $rawText,
                errors: []
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
        // Skip non-product lines early
        if ($this->isNonProductLine($line)) {
            return null;
        }

        // AH Format 1: Qty at start with unit price and total
        // "4        PAPRIKA              0,89     3,56 B"
        // "2        AH EMMENTALE         2,79     5,58 B"
        if (preg_match('/^(\d+)\s+(.+?)\s+(\d+[,.]\d{2})\s+(\d+[,.]\d{2})\s*([B%].*)?$/u', $line, $matches)) {
            $isBonus = !empty($matches[5]) && str_contains($matches[5], 'B');
            return new ParsedLine(
                name: trim($matches[2]),
                quantity: (int)$matches[1],
                unitPrice: $this->parsePrice($matches[3]),
                totalPrice: $this->parsePrice($matches[4]),
                isBonus: $isBonus,
                rawText: $line
            );
        }

        // AH Format 2: Qty at start with only total price
        // "1        WINTERPEEN                    0,35"
        // "1        BEEMSTER                    10,27 35%"
        if (preg_match('/^(\d+)\s+(.+?)\s+(\d+[,.]\d{2})\s*(%.*|B)?$/u', $line, $matches)) {
            $name = trim($matches[2]);
            // Skip if name looks like a non-product
            if ($this->isNonProductLine($name)) {
                return null;
            }

            $isBonus = !empty($matches[4]) && str_contains($matches[4], 'B');
            $totalPrice = $this->parsePrice($matches[3]);
            $quantity = (int)$matches[1];
            $unitPrice = $quantity > 0 ? $totalPrice / $quantity : $totalPrice;

            return new ParsedLine(
                name: $name,
                quantity: $quantity,
                unitPrice: round($unitPrice, 2),
                totalPrice: $totalPrice,
                isBonus: $isBonus,
                rawText: $line
            );
        }

        return null;
    }

    protected function isNonProductLine(string $name): bool
    {
        $nonProductKeywords = [
            'subtotaal',
            'totaal',
            'te betalen',
            'statiegeld',
            '+statiegeld',
            'btw',
            'pin',
            'pinnen',
            'contant',
            'retour',
            'korting',
            'bonus',
            'actie',
            'bonuskaart',
            'airmiles',
            'koopzegels',
            'spaaracties',
            'espaarzegelspremium',
            'betaald',
            'uw voordeel',
            'waarvan',
            'aantal',
            'omschrijving',
            'prijs',
            'bedrag',
        ];

        $lowerName = strtolower(trim($name));

        // Skip lines starting with + (deposits)
        if (str_starts_with($lowerName, '+')) {
            return true;
        }

        foreach ($nonProductKeywords as $keyword) {
            if (str_contains($lowerName, $keyword)) {
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
}
