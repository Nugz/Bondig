---
stepsCompleted: [1, 2, 3]
workflowType: 'research'
research_type: 'technical'
research_topic: 'PHP/Laravel PDF Parsing & AI Categorization APIs for Bondig'
research_goals: 'Find free/cheap, Dutch-supporting solutions for receipt parsing and product categorization'
user_name: 'Bram'
date: '2026-01-02'
web_research_enabled: true
source_verification: true
---

# Technical Research Report: PDF Parsing & AI Categorization for Bondig

**Date:** 2026-01-02
**Author:** Bram
**Research Type:** Technical
**Project:** Bondig - Personal Grocery Spending Tracker

---

## Executive Summary

This research evaluates technical options for Bondig's two core challenges:
1. **PDF Parsing:** Extracting text from Albert Heijn receipt PDFs
2. **AI Categorization:** Auto-categorizing Dutch product names into spending categories

**Key Findings:**
- **PDF Parsing:** `spatie/pdf-to-text` is the clear winner - free, Laravel-native, 5.8M+ installs, perfect for text-based PDFs
- **AI Categorization:** Google Gemini offers a free tier sufficient for personal use; paid fallback at ~$0.15/1M tokens

**Recommendation:** Both requirements can be met with FREE solutions for Bondig's low-volume personal use case.

---

## Part 1: PHP/Laravel PDF Text Extraction

### Requirements Recap
- Extract text from AH receipt PDFs
- AH receipts are TEXT-BASED (not scanned images)
- Must be free and work with Laravel
- Self-hosted (no cloud dependency for parsing)

### Top Libraries Evaluated

#### 1. spatie/pdf-to-text (RECOMMENDED)

| Attribute | Value |
|-----------|-------|
| Packagist | [spatie/pdf-to-text](https://packagist.org/packages/spatie/pdf-to-text) |
| GitHub | [github.com/spatie/pdf-to-text](https://github.com/spatie/pdf-to-text) |
| Installs | 5.8+ million |
| Stars | 992 |
| Latest | v1.55.0 (November 2025) |
| PHP | ^8.4 |
| Cost | FREE |

**Why It's Best for Bondig:**
- "De-facto standard in Laravel tutorials on PDF text extraction"
- Uses native `pdftotext` binary (from poppler-utils)
- Reads text objects as embedded in PDF - **no OCR guessing**
- Low memory consumption - handles 500+ page documents
- Maintained by Spatie (most respected Laravel package maintainer)

**Usage:**
```php
use Spatie\PdfToText\Pdf;

// Simple extraction
$text = Pdf::getText('receipt.pdf');

// With custom binary path
$text = (new Pdf('/usr/bin/pdftotext'))
    ->setPdf('receipt.pdf')
    ->text();
```

**Server Requirement:**
```bash
# Ubuntu/Debian
sudo apt-get install poppler-utils

# macOS
brew install poppler
```

#### 2. smalot/pdfparser (Alternative)

| Attribute | Value |
|-----------|-------|
| Packagist | [smalot/pdfparser](https://packagist.org/packages/smalot/pdfparser) |
| GitHub | [github.com/smalot/pdfparser](https://github.com/smalot/pdfparser) |
| Installs | 29+ million |
| Stars | 2,617 |
| PHP | 7.1+ |
| Cost | FREE |

**Pros:**
- Pure PHP - no external binary required
- Extracts metadata (author, description, etc.)
- Supports compressed PDFs

**Cons:**
- Less accurate for complex layouts
- Heavier memory usage
- No support for secured documents

**Usage:**
```php
$parser = new \Smalot\PdfParser\Parser();
$pdf = $parser->parseFile('receipt.pdf');
$text = $pdf->getText();
```

### Recommendation

**Use `spatie/pdf-to-text`** for Bondig:
- AH receipts are simple, text-based PDFs - perfect match
- Higher accuracy due to native pdftotext
- Better maintained, Laravel-ecosystem standard
- Server dependency (poppler-utils) is trivial to install

---

## Part 2: AI Categorization APIs

### Requirements Recap
- Auto-categorize products: "BEEMSTER" → Cheese/Dairy
- Dutch language support critical
- Free or very cheap
- Cloud API acceptable

### Volume Estimate for Bondig

| Metric | Value |
|--------|-------|
| Receipts per week | ~4 |
| Items per receipt | ~40 |
| Categorizations per week | ~160 |
| Categorizations per month | ~640 |
| Tokens per request (est.) | ~100 |
| Tokens per month | ~64,000 |

**Conclusion:** Bondig's volume is TINY - free tiers are more than sufficient.

### API Pricing Comparison (2025)

| Provider | Model | Input (per 1M) | Output (per 1M) | Free Tier |
|----------|-------|----------------|-----------------|-----------|
| **Google Gemini** | 2.5 Flash-Lite | ~$0.15 | ~$0.60 | 1,000 req/day |
| **Google Gemini** | 2.5 Flash | ~$0.15 | ~$0.60 | 50 req/day |
| **DeepSeek** | V3.2 | $0.27 | $1.10 | None |
| **OpenAI** | GPT-4o mini | $0.60 | $2.40 | Limited |
| **Anthropic** | Claude Haiku 3.5 | $0.80 | $4.00 | None |

**Sources:**
- [LLM API Pricing Comparison 2025 - IntuitionLabs](https://intuitionlabs.ai/articles/llm-api-pricing-comparison-2025)
- [Gemini API Pricing - Google](https://ai.google.dev/gemini-api/docs/pricing)
- [AI API Pricing Comparison - IntuitionLabs](https://intuitionlabs.ai/articles/ai-api-pricing-comparison-grok-gemini-openai-claude)

### Google Gemini (RECOMMENDED)

**Why Gemini for Bondig:**
- **FREE tier:** 1,000 requests/day on Flash-Lite = ~30,000/month
- Bondig needs ~640/month = **well within free tier**
- Good multilingual support including Dutch
- No credit card required to start
- Available in 180+ countries

**Free Tier Limits (as of Dec 2025):**

| Model | Requests/Day | Requests/Min |
|-------|--------------|--------------|
| Gemini 2.5 Flash-Lite | 1,000 | 15 |
| Gemini 2.5 Flash | 50 | 5 |
| Gemini 2.5 Pro | 25 | 5 |

**Note:** Free tier limits were reduced in December 2025, but Flash-Lite's 1,000/day is still abundant for Bondig.

**Source:** [Gemini API Free Quota 2025 - AI Free API](https://www.aifreeapi.com/en/posts/gemini-api-free-quota)

### Dutch Language Support

Research on Dutch LLM performance:

- **Qwen2.5 series** shows "superior performance across most Dutch language benchmarks"
- Modern multilingual models (Gemini, GPT-4, Claude) handle Dutch well
- For simple categorization tasks, any major model will work
- Product names like "BEEMSTER", "CAMPINA VLA" are recognizable brand names

**Sources:**
- [Dutch-LLMs GitHub Repository](https://github.com/RobinSmits/Dutch-LLMs)
- [Compact Dutch Language Models - Medium](https://medium.com/@sarthakanand/compact-dutch-language-models-efficiency-without-compromise-bf8c1e441b96)

### Implementation Approach

**Recommended Prompt Strategy:**
```
Categorize this Dutch grocery product into one of these categories:
[Dairy, Meat, Vegetables, Fruits, Beverages, Snacks, Bread/Bakery,
Frozen, Household, Other]

Product: "BEEMSTER"
Category:
```

**Batch Processing:**
- Group 10-20 products per API call to reduce request count
- Further reduces usage, extends free tier longevity

---

## Part 3: Recommendations Summary

### PDF Parsing

| Decision | Choice |
|----------|--------|
| Library | `spatie/pdf-to-text` |
| Cost | FREE |
| Complexity | Low (one composer require + apt install) |
| Confidence | HIGH |

**Installation:**
```bash
composer require spatie/pdf-to-text
sudo apt-get install poppler-utils
```

### AI Categorization

| Decision | Choice |
|----------|--------|
| Primary API | Google Gemini (Flash-Lite) |
| Cost | FREE (within limits) |
| Fallback | DeepSeek V3.2 ($0.27/1M - cheapest paid) |
| Confidence | HIGH |

**Implementation Priority:**
1. Start with Gemini free tier
2. Batch requests (10-20 products per call)
3. Cache categorizations - same product = same category
4. Only call API for NEW products

### Cost Projection

| Scenario | Monthly Cost |
|----------|--------------|
| Normal usage (free tier) | $0.00 |
| Exceeded free tier (unlikely) | ~$0.01-0.05 |
| Worst case (no caching) | ~$0.10 |

**Bondig can run entirely FREE.**

---

## Technical Architecture Recommendation

```
Receipt Upload (PDF)
       ↓
spatie/pdf-to-text (FREE, self-hosted)
       ↓
Parse product lines (regex)
       ↓
Check product cache
       ↓
[If new product] → Gemini API (FREE tier)
       ↓
Store category + cache for future
       ↓
Dashboard display
```

---

## Next Steps

1. **Set up Laravel project** with spatie/pdf-to-text
2. **Test PDF extraction** on real AH receipts
3. **Create Gemini API account** (no credit card needed)
4. **Build categorization service** with caching
5. **Define category taxonomy** (10-15 categories)

---

## Sources

### PDF Parsing
- [spatie/pdf-to-text - GitHub](https://github.com/spatie/pdf-to-text)
- [smalot/pdfparser - GitHub](https://github.com/smalot/pdfparser)
- [Best Practices for Reading PDFs in Laravel 2025](https://blog.greeden.me/en/2025/12/05/best-practices-for-reading-pdfs-in-laravel)
- [Definitive Guide to Laravel PDF Processing 2025](https://blog.greeden.me/en/2025/12/05/definitive-guide-to-laravel-x-pdf-processing-accuracy-focused-ocr-llm-ranking-comparison-table)

### AI API Pricing
- [LLM API Pricing Comparison 2025 - IntuitionLabs](https://intuitionlabs.ai/articles/llm-api-pricing-comparison-2025)
- [AI API Pricing Comparison - IntuitionLabs](https://intuitionlabs.ai/articles/ai-api-pricing-comparison-grok-gemini-openai-claude)
- [Gemini API Pricing - Google](https://ai.google.dev/gemini-api/docs/pricing)
- [Gemini API Free Quota 2025](https://www.aifreeapi.com/en/posts/gemini-api-free-quota)

### Dutch Language Models
- [Dutch-LLMs Repository - GitHub](https://github.com/RobinSmits/Dutch-LLMs)
- [Compact Dutch Language Models - Medium](https://medium.com/@sarthakanand/compact-dutch-language-models-efficiency-without-compromise-bf8c1e441b96)

---

**Research completed: 2026-01-02**
