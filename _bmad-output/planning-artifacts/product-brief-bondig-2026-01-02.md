---
stepsCompleted: [1, 2, 3, 4, 5]
inputDocuments:
  - "_bmad-output/analysis/brainstorming-session-2026-01-02.md"
  - "_bmad-output/planning-artifacts/research/technical-bondig-pdf-ai-research-2026-01-02.md"
date: "2026-01-02"
author: "Bram"
project_name: "Bondig"
---

# Product Brief: Bondig

## Executive Summary

**Bondig** is a personal grocery spending analytics tool that transforms receipt data into actionable insights. Unlike generic budget apps that only show "you spent €X at Albert Heijn," Bondig answers the deeper questions: *What am I actually spending money on? Why is my spending increasing? Where can I optimize?*

Built for personal use with a focus on Dutch supermarkets (primarily Albert Heijn), Bondig provides monthly reflection reports with category breakdowns, price trend tracking, and budget monitoring - turning scattered receipt data into a clear picture of grocery spending patterns.

**Core Value:** Upload receipts → Understand spending → Save money.

---

## Core Vision

### Problem Statement

Monthly grocery spending keeps increasing, but it's nearly impossible to understand why. Is it inflation? Buying more expensive products? Purchasing more quantity? Without visibility into spending patterns at the product and category level, there's no way to make informed decisions about where to optimize.

### Problem Impact

- **Financial blindness:** Money flows out without understanding where it goes
- **Missed savings:** No visibility into cheaper alternatives or price increases
- **Budget frustration:** Setting budgets without historical context leads to unrealistic targets
- **Invisible inflation:** Small price increases on regular purchases compound into significant yearly costs (a €0.20/week increase = €10.40/year per item)

### Why Existing Solutions Fall Short

| Solution Type | What It Does | What It Lacks |
|---------------|--------------|---------------|
| Budget apps (YNAB, Mint) | Tracks spending by merchant | No product-level visibility |
| Receipt scanners | Digitizes receipts for expense reports | No analytics or trends |
| Supermarket apps (AH) | Shows purchase history | No category analysis, no price tracking |
| Spreadsheets | Manual tracking possible | Time-consuming, no automation |

**The Gap:** No tool answers "WHY is my grocery spending increasing?" or "WHERE can I save money?" at the product level.

### Proposed Solution

Bondig is a **personal grocery spending detective** - a monthly reflection tool that:

1. **Ingests receipts** via PDF upload or photo (optimized for Albert Heijn)
2. **Parses and categorizes** products automatically using AI, with manual override
3. **Tracks prices over time** to identify inflation on your regular purchases
4. **Visualizes spending** by category with drill-down to product level
5. **Monitors budget** against a simple monthly target
6. **Surfaces insights** like recurring purchase costs annualized

**Design Philosophy:** Reflection tool, not real-time assistant. Batch processing, not constant engagement. Power user dashboard, not oversimplified summaries.

### Key Differentiators

1. **Product-level analytics** - See exactly what you're buying, not just where you're shopping
2. **Price trend tracking** - Know when your regular items get more expensive
3. **Optimized for Dutch supermarkets** - Built specifically for AH receipt format
4. **Personal-use simplicity** - No multi-user complexity, no enterprise features
5. **Historical insight from day one** - 12 months of receipt history available to bootstrap
6. **AI-assisted categorization** - Automatic with learning from your corrections

---

## Target Users

### Primary User

**Bram** - Solo household grocery manager

| Attribute | Details |
|-----------|---------|
| Role | Primary household shopper |
| Technical level | Intermediate - comfortable building own tools |
| Primary store | Albert Heijn (90%+ of purchases) |
| Shopping pattern | 1-2 big weekly trips + occasional small ones |
| Receipt access | Digital PDFs from AH, occasional physical from other stores |

**Motivations:**
- Understand where grocery money actually goes
- Identify price increases on regular purchases
- Set and track realistic grocery budgets
- Find optimization opportunities to save money

**Current Pain:**
- No visibility into spending patterns at product level
- Can't answer "why did spending increase this month?"
- No easy way to track prices over time
- Budget setting is guesswork without historical data

**Success Looks Like:**
- Monthly report that reveals spending patterns
- Clear visibility into price trends
- Confidence in budget targets based on real data
- Actual money saved through informed decisions

### Secondary Users

None - this is a personal tool with no multi-user requirements.

### User Journey

**Weekly Workflow:**

```
Weekend Shopping
      ↓
Receive digital receipt (AH app/email)
      ↓
Weekly: Batch upload receipts to Bondig
      ↓
Quick review: "12 items need categorization"
      ↓
Fix/confirm categories (2-3 minutes)
      ↓
Monthly: Review dashboard and insights
      ↓
Adjust budget or shopping habits as needed
```

**Key Moments:**

| Moment | Experience |
|--------|------------|
| **First upload** | "Wow, it actually parsed everything correctly" |
| **First month review** | "So THAT'S where the money goes" |
| **Price alert** | "Butter is 20% more expensive than 3 months ago" |
| **Budget win** | "I came in under budget by making informed swaps" |

---

## Success Metrics

### Primary Success Metric

**Reduce monthly grocery spending by 15%** through insights-driven decisions.

| Metric | Target | Measurement |
|--------|--------|-------------|
| Monthly spending reduction | 15% vs baseline | Compare 3-month average before vs after Bondig |
| Baseline establishment | Month 1-2 | Load historical receipts, establish current spending |
| Target achievement | Month 4+ | Track actual vs baseline spending |

### Supporting Success Indicators

**Usage Metrics (Leading Indicators):**
- Receipts uploaded weekly (consistency)
- Categories reviewed and corrected (data quality)
- Monthly dashboard reviewed (engagement)

**Insight Metrics (Value Creation):**
- Price increases identified and acted upon
- Category spending anomalies discovered
- Budget adherence improved over time

**Outcome Metrics (Lagging Indicators):**
- Actual savings per month vs baseline
- Spending decisions changed based on insights
- Budget targets met consistently

### Success Timeline

| Timeframe | Milestone |
|-----------|-----------|
| **Month 1** | Historical data loaded, baseline established |
| **Month 2** | First full month of active tracking |
| **Month 3** | Patterns identified, first optimization decisions |
| **Month 4+** | Measure actual savings vs 15% target |

### What Success Feels Like

- "I know exactly where my grocery money goes"
- "I spotted that price increase and switched products"
- "I hit my budget target this month"
- "I'm spending 15% less without feeling like I'm sacrificing"

---

## MVP Scope

### Core Features (MVP)

| # | Feature | Description |
|---|---------|-------------|
| 1 | **AH PDF Receipt Upload** | Single + batch upload of Albert Heijn PDF receipts |
| 2 | **Receipt Parsing** | Extract products, prices, quantities, dates from AH PDFs |
| 3 | **Manual Receipt Input** | Full product-level entry for non-AH stores |
| 4 | **AI Auto-Categorization** | Automatic product categorization with manual override |
| 5 | **Category Dashboard** | Spending by category with drill-down to products |
| 6 | **Flexible Time Ranges** | Select any time window + comparison period |
| 7 | **Price Tracking** | Track same product prices over time |
| 8 | **Monthly Budget** | Single budget target with progress tracking |
| 9 | **Product Merge** | Manually combine duplicate products |

### Out of Scope (V2+)

| Feature | Reason for Deferral |
|---------|---------------------|
| Photo OCR (other stores) | Complexity - manual input covers this for MVP |
| Recurring purchase detection | Enhancement - need baseline data first |
| Annualized impact view | Enhancement - builds on recurring detection |
| Year-over-year comparisons | Need 12+ months of data |
| Cheaper alternatives suggestions | Requires product database/API |
| Mobile app | Web-based is sufficient for weekly batch workflow |
| Shopping list integration | Out of scope - separate tool (Bring) |
| Real-time alerts | Reflection tool, not intervention tool |

### MVP Success Criteria

The MVP is "done" when:

1. **Core Loop Works:** Upload receipt → Parse → Categorize → View dashboard
2. **Manual Fallback:** Can enter non-AH receipts with full detail
3. **Insights Visible:** Category breakdown + price trends visible
4. **Budget Tracking:** Can set and track monthly budget
5. **Historical Data:** 12 months of receipts loaded and analyzed

**Go/No-Go Signal:** After 1 month of active use, can you answer "why did spending change?" using Bondig data?

### Future Vision (V2+)

Once MVP proves value:

| Phase | Features |
|-------|----------|
| **V2** | Photo OCR for other stores, recurring purchase detection, annualized impact |
| **V3** | Smart recommendations, cheaper alternatives, predictive budgeting |
| **Long-term** | Multi-store price comparison, household sharing, expense categories beyond groceries |

---

