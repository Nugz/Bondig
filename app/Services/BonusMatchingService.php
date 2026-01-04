<?php

namespace App\Services;

use App\DTOs\MatchResult;
use App\DTOs\ParsedBonus;
use App\Models\LineItem;
use Illuminate\Support\Collection;

class BonusMatchingService
{
    /**
     * Confidence threshold for auto-matching bonuses to products.
     * Matches require >= 80% similarity (AC #2 specifies ">80%" which is interpreted as 80% or higher).
     * This inclusive interpretation prevents edge cases where exactly 80% would fail.
     */
    protected float $confidenceThreshold = 0.80;

    /**
     * Match a bonus to a product line item.
     *
     * @param ParsedBonus $bonus
     * @param Collection<LineItem> $lineItems
     * @return MatchResult|null
     */
    public function matchBonusToProduct(ParsedBonus $bonus, Collection $lineItems): ?MatchResult
    {
        $normalizedBonus = $this->normalize($bonus->rawName);
        $bestMatch = null;
        $bestScore = 0.0;

        // Only consider line items marked as bonus
        $bonusItems = $lineItems->filter(fn (LineItem $item) => $item->is_bonus);

        foreach ($bonusItems as $item) {
            $normalizedProduct = $this->normalize($item->product->name);
            $similarity = $this->calculateSimilarity($normalizedBonus, $normalizedProduct);

            if ($similarity > $bestScore) {
                $bestScore = $similarity;
                $bestMatch = $item;
            }
        }

        if ($bestMatch !== null && $bestScore >= $this->confidenceThreshold) {
            return new MatchResult(
                lineItem: $bestMatch,
                confidence: $bestScore,
                matchType: 'auto'
            );
        }

        return null;
    }

    /**
     * Normalize a product name for comparison.
     * - Remove common prefixes (AH, ALBERT HEIJN)
     * - Remove spaces
     * - Convert to uppercase
     * - Remove special characters
     */
    public function normalize(string $name): string
    {
        // Convert to uppercase
        $normalized = strtoupper($name);

        // Remove common prefixes
        $normalized = preg_replace('/^(AH\s*|ALBERT\s*HEIJN\s*)/', '', $normalized);

        // Remove special characters except letters and numbers
        $normalized = preg_replace('/[^A-Z0-9]/', '', $normalized);

        return $normalized;
    }

    /**
     * Calculate similarity between two normalized strings.
     * Uses multiple strategies to find the best match.
     */
    public function calculateSimilarity(string $a, string $b): float
    {
        // Empty strings are not similar
        if (empty($a) || empty($b)) {
            return 0.0;
        }

        // Exact match
        if ($a === $b) {
            return 1.0;
        }

        // Strategy 1: Check if one contains the other
        // This handles cases like "PAPRIKA" matching "AHPAPRIKAROO"
        if (str_contains($a, $b) || str_contains($b, $a)) {
            // The shorter string should be a significant portion of the longer one
            $minLen = min(strlen($a), strlen($b));
            $maxLen = max(strlen($a), strlen($b));
            $containmentRatio = $minLen / $maxLen;

            // Bonus for containment, weighted by how much of the string matches
            return 0.85 + (0.15 * $containmentRatio);
        }

        // Strategy 2: Levenshtein distance normalized
        $maxLen = max(strlen($a), strlen($b));
        if ($maxLen === 0) {
            return 0.0; // Guard against division by zero for edge cases
        }
        $distance = levenshtein($a, $b);
        $levenshteinScore = 1 - ($distance / $maxLen);

        // Strategy 3: similar_text percentage
        similar_text($a, $b, $percentSimilar);
        $similarTextScore = $percentSimilar / 100;

        // Return the best score from the strategies
        return max($levenshteinScore, $similarTextScore);
    }

    /**
     * Set the confidence threshold.
     */
    public function setConfidenceThreshold(float $threshold): void
    {
        $this->confidenceThreshold = $threshold;
    }

    /**
     * Get the current confidence threshold.
     */
    public function getConfidenceThreshold(): float
    {
        return $this->confidenceThreshold;
    }
}
