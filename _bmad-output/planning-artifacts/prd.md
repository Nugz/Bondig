---
stepsCompleted: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11]
inputDocuments:
  - "_bmad-output/planning-artifacts/product-brief-bondig-2026-01-02.md"
  - "_bmad-output/planning-artifacts/research/technical-bondig-pdf-ai-research-2026-01-02.md"
  - "_bmad-output/analysis/brainstorming-session-2026-01-02.md"
workflowType: 'prd'
lastStep: 0
documentCounts:
  briefs: 1
  research: 1
  brainstorming: 1
  projectDocs: 0
---

# Product Requirements Document - Bondig

**Author:** Bram
**Date:** 2026-01-02

## Executive Summary

**Bondig** is a personal grocery spending analytics tool that transforms Albert Heijn receipt data into actionable insights. Built as a monthly reflection tool rather than a real-time shopping assistant, Bondig answers the questions that budget apps and supermarket apps can't: *Why is my grocery spending increasing? Where is my money actually going? Where can I optimize?*

The core workflow is simple: upload PDF receipts weekly, review AI-suggested categories, and at month's end get a clear picture of spending patterns with drill-down from category to product level. With 12 months of historical receipts ready to load, Bondig delivers value from day one.

**Target User:** Solo household manager shopping primarily at Albert Heijn (1-2 weekly trips), seeking visibility into grocery spending without the overhead of complex budgeting tools.

### What Makes This Special

**Product-level visibility into grocery spending** - the ability to answer "WHY is spending increasing?" and "WHERE can I save?" at the item level, not just the merchant level.

Key differentiators:
1. **Product-level analytics** - See exactly what you're buying, not just where you're shopping
2. **Price trend tracking** - Catch inflation on your regular purchases before it compounds
3. **Dutch supermarket optimization** - Purpose-built for AH text-based PDF receipts (no OCR complexity)
4. **Personal-use simplicity** - No multi-user overhead, just pure utility
5. **Instant historical insight** - 12 months of receipt data ready to bootstrap

## Project Classification

**Technical Type:** web_app
**Domain:** general
**Complexity:** low
**Project Context:** Greenfield - new project

This is a Laravel-based web application for personal use. Despite dealing with spending data, it operates in the general domain (not fintech) since it analyzes personal data without payment processing or regulatory requirements. Technical complexity is contained to PDF text extraction and AI categorization - both with clear, researched solutions.

## Success Criteria

### User Success

**Leading Indicator (Month 1):**
- Clear visibility into product-level spending - can confidently answer "what am I spending money on?"
- All historical receipts (12 months) loaded and categorized
- Dashboard shows meaningful category breakdown with drill-down capability

**Lagging Indicator (Month 4+):**
- 15% reduction in monthly grocery spending through insights-driven decisions
- Consistent budget target achievement
- Active use of price trend data to inform purchasing decisions

**Emotional Success:**
- "I know exactly where my grocery money goes"
- "I spotted that price increase and switched products"
- "I'm spending 15% less without feeling like I'm sacrificing"

### Business Success

**Personal Value:**
- Tool remains useful for long-term use (years, not months)
- Weekly upload habit is sustainable (< 5 minutes/week)
- Monthly review provides consistent value

**Future Potential:**
- Architecture allows for potential sharing with others if proven valuable
- No hard-coded personal assumptions that would block future expansion

**Abandonment Trigger (Inverse Success):**
- Data accuracy issues that erode trust in the numbers
- If parsing/categorization requires too much manual effort to be worth it

### Technical Success

**Philosophy:** Good enough automation + easy manual cleanup

**Receipt Parsing:**
- AH PDF text extraction works reliably (text-based PDFs, no OCR complexity)
- Errors are visible and easy to correct
- Batch upload handles multiple receipts efficiently

**Categorization:**
- AI provides reasonable first-pass categorization
- System learns from user corrections over time
- Review/correction workflow takes minutes, not hours

**Data Integrity:**
- Manual product merge available as safety net for matching issues
- Price tracking correctly associates same products over time
- No silent data corruption - errors surface visibly

### Measurable Outcomes

| Metric | Target | Measurement Point |
|--------|--------|-------------------|
| Product visibility | 100% of purchases categorized | Month 1 |
| Spending reduction | 15% vs baseline | Month 4+ |
| Weekly effort | < 5 minutes | Ongoing |
| Parsing accuracy | 95%+ (with easy correction for remainder) | Ongoing |
| Trust level | User confidently makes decisions based on data | Month 2+ |

## Product Scope

### MVP - Minimum Viable Product

**Core Loop:** Upload receipt → Parse → Categorize → View insights

| Feature | Description |
|---------|-------------|
| AH PDF Upload | Single + batch upload of Albert Heijn PDF receipts |
| Receipt Parsing | Extract products, prices, quantities, dates from AH text-based PDFs |
| AI Auto-Categorization | Automatic product categorization with manual override + learning |
| Category Dashboard | Spending by category with drill-down to product level |
| Flexible Time Ranges | Select any time window + comparison period |
| Price Tracking | Track same product prices over time |
| Monthly Budget | Single budget target with progress tracking |
| Product Merge | Manually combine duplicate/variant products |
| Manual Receipt Entry | Full product-level entry for non-AH purchases |

**MVP Done When:**
- Core loop works end-to-end
- 12 months historical data loaded
- Can answer "why did spending change?" using Bondig data

### Growth Features (Post-MVP)

| Feature | Rationale |
|---------|-----------|
| Photo OCR | Support non-AH stores with physical receipts |
| Recurring Purchase Detection | Identify weekly staples automatically |
| Annualized Impact View | "This €0.50/week increase = €26/year" |
| Year-over-Year Comparison | Requires 12+ months of active data |

### Vision (Future)

| Feature | Rationale |
|---------|-----------|
| Cheaper Alternatives | Suggest swaps based on price tracking |
| Multi-Store Comparison | Compare prices across different stores |
| Sharing/Multi-User | If proven valuable, allow household sharing |
| Predictive Budgeting | Forecast spending based on patterns |

## User Journeys

### Journey 1: The Great Receipt Excavation (First-Time Setup)

Bram has been wondering for months why his grocery bills keep climbing. He's got 12 months of Albert Heijn PDF receipts sitting in his email, representing hundreds of shopping trips and thousands of euros spent - but zero insight into where it all went. Tonight, he decides to finally do something about it.

He fires up Bondig and drags his first batch of PDFs into the upload zone - 50 receipts from the last few months. Within seconds, the parser extracts 1,847 line items. The AI takes its first pass at categorization: Dairy, Meat, Produce, Snacks... most look right, but "BEEMSTER" is tagged as "Unknown" and "AH TERRA CHIPS" landed in "Household" instead of "Snacks."

Bram spends 15 minutes reviewing and correcting the obvious mistakes. Each correction teaches the system. By the time he uploads the remaining months of receipts, the AI is noticeably smarter - "BEEMSTER" now correctly lands in "Dairy/Cheese" without prompting.

After an hour of setup, Bram looks at his dashboard for the first time. The category breakdown hits him: **Snacks and convenience foods are 23% of his grocery spending.** He had no idea. The foundation is set - now he can actually see where his money goes.

**Requirements revealed:**
- Batch PDF upload (drag-and-drop multiple files)
- Fast parsing with progress indication
- AI categorization with visible confidence levels
- Easy category correction workflow
- System learning from corrections
- Dashboard with category breakdown

---

### Journey 2: The Sunday Evening Ritual (Weekly Routine)

It's Sunday evening. Bram just finished his weekly Albert Heijn run - €127.43 this time. He opens his email, downloads the PDF receipt, and drops it into Bondig.

The system parses 34 items in under 3 seconds. A small badge appears: "3 items need review." He clicks through - two new products he hasn't bought before (the AI guessed "Beverages" and "Snacks" - both correct), and one weird abbreviation "AH BIO GRK YOG" that the AI couldn't confidently categorize. He taps "Dairy" and confirms.

Total time: 2 minutes. His budget tracker updates: €389 spent this month, €111 remaining with 8 days to go. He's on track.

The habit sticks because it's fast. Upload, quick review, done. No friction, no complexity.

**Requirements revealed:**
- Single receipt upload (quick path)
- Minimal review workflow (only show items needing attention)
- Clear confidence indication for AI suggestions
- One-tap category confirmation
- Running budget display with time context
- Sub-5-minute weekly workflow

---

### Journey 3: The Monthly Reckoning (Dashboard Deep-Dive)

It's the first Saturday of the month. Bram pours a coffee and opens Bondig's dashboard for his monthly reflection ritual.

The overview shows: **€487 spent in December** - up 12% from November. His gut tightens. But now, unlike before, he can actually investigate why.

He clicks into the category breakdown. Dairy jumped from €62 to €89. He drills down - butter and cheese prices have crept up across the board. His regular BEEMSTER went from €5.49 to €6.29 over three months. That's €3.20/month extra on just one product.

He checks "Snacks" - still too high at €94. But he spots something: he bought premium crisps 6 times last month when the AH house brand is half the price. That's an easy €15/month savings right there.

He sets his January budget at €450 and makes a mental note: house brand crisps, watch the cheese prices. For the first time, his budget target is based on real data, not a guess.

**Requirements revealed:**
- Monthly spending overview with trend comparison
- Category breakdown with drill-down to product level
- Price history per product over time
- Period comparison (this month vs last month)
- Visual spending trends
- Budget setting informed by historical data

---

### Journey 4: The Lidl Exception (Edge Cases & Error Handling)

Bram stops at Lidl on the way home - unusual, but they had a deal on olive oil. The paper receipt goes in his pocket. Later, he realizes he wants this in Bondig too for completeness.

He opens Manual Entry, types "Lidl" as the store, enters the date, and adds three line items: olive oil €4.99, bread €1.29, bananas €1.49. He assigns categories manually and saves.

The next week, he uploads an AH receipt and notices a parsing error - a product line got mangled and shows "€34.56" for something that should be €3.45. He clicks the item, sees the original text from the PDF, manually corrects the price, and flags the pattern so he can report it later if it keeps happening.

Nothing is perfect, but everything is fixable. The data stays trustworthy because he stays in control.

**Requirements revealed:**
- Manual receipt entry (non-AH stores)
- Store field for multi-store tracking
- Line item editing for parsing errors
- View original parsed text (debug mode)
- Error correction workflow
- Data integrity through user control

---

### Journey Requirements Summary

| Journey | Key Capabilities Required |
|---------|--------------------------|
| First-Time Setup | Batch upload, AI categorization, correction workflow, learning system |
| Weekly Routine | Quick upload, minimal review, budget tracking, fast workflow |
| Monthly Reflection | Trend analysis, category drill-down, price history, comparison views |
| Edge Cases | Manual entry, error correction, data control, multi-store support |

**Core UX Principle:** Automate the tedious, surface what matters, keep the user in control.

## Web Application Requirements

### Architecture Approach

**Type:** Traditional MPA (Multi-Page Application)
**Framework:** Laravel with server-rendered views
**Rationale:** Simplicity over complexity - no need for SPA overhead for a personal reflection tool

### Browser Support

| Browser | Support Level |
|---------|---------------|
| Chrome (latest) | Full support |
| Firefox (latest) | Full support |
| Safari (latest) | Best effort |
| Edge (latest) | Best effort |
| IE / Legacy | Not supported |

**Target:** Modern evergreen browsers only. No polyfills, no legacy accommodations.

### Technical Constraints

| Aspect | Decision | Rationale |
|--------|----------|-----------|
| SEO | Not required | Personal tool, no public discovery needed |
| Real-time | Not required | Request-response is sufficient for batch workflow |
| Accessibility | Not prioritized | Single-user personal tool |
| Offline | Not required | Always-connected use case |
| Mobile-responsive | Nice-to-have | Primary use is desktop, but basic mobile viewing acceptable |

### Performance Targets

| Metric | Target | Context |
|--------|--------|---------|
| Page load | < 2 seconds | Dashboard and list views |
| PDF parsing | < 5 seconds per receipt | Background processing acceptable |
| AI categorization | < 10 seconds per batch | Async processing with status indicator |

### Implementation Stack

| Layer | Technology | Notes |
|-------|------------|-------|
| Backend | Laravel | PHP framework, your preference |
| Views | Blade templates | Server-rendered, simple |
| Interactivity | Alpine.js / Livewire (optional) | Lightweight reactivity where needed |
| Styling | Tailwind CSS (recommended) | Utility-first, fast iteration |
| Database | SQLite or MySQL | SQLite fine for personal use |

## Functional Requirements

### Receipt Ingestion

- FR1: User can upload a single AH PDF receipt via drag-and-drop or file picker
- FR2: User can upload multiple AH PDF receipts in a single batch operation
- FR3: User can manually enter a receipt for non-AH stores (store name, date, line items)
- FR4: System can detect and reject duplicate receipts (same store + date + total)

### Data Parsing & Extraction

- FR5: System can extract product names from AH PDF receipts
- FR6: System can extract product quantities from AH PDF receipts
- FR7: System can extract product prices (unit and total) from AH PDF receipts
- FR8: System can extract receipt date and time from AH PDF receipts
- FR9: System can extract receipt total amount from AH PDF receipts
- FR10: System can identify bonus/discount items from AH PDF receipts
- FR11: User can view the original parsed text for any line item (debug mode)

### Product Categorization

- FR12: System can auto-categorize products using AI
- FR13: System can display confidence level for each AI categorization
- FR14: User can override any product category assignment
- FR15: System can learn from user corrections to improve future categorizations
- FR16: User can view items needing category review (low-confidence items)
- FR17: User can confirm AI-suggested categories with single action

### Product Management

- FR18: User can view all unique products across all receipts
- FR19: User can merge duplicate/variant products into a single product
- FR20: User can edit product details (name, category) for any product
- FR21: User can view purchase history for any product

### Spending Analytics

- FR22: User can view total spending for any selected time period
- FR23: User can view spending breakdown by category
- FR24: User can drill down from category to individual products
- FR25: User can compare spending between two time periods
- FR26: User can view spending trends over time (visual)
- FR27: User can filter analytics by store

### Price Tracking

- FR28: System can track price changes for the same product over time
- FR29: User can view price history for any product
- FR30: User can identify products with price increases

### Budget Management

- FR31: User can set a monthly budget target
- FR32: User can view current month spending vs budget
- FR33: User can view remaining budget with days left in month
- FR34: User can view budget history (actual vs target by month)

### Data Correction

- FR35: User can edit any line item on a parsed receipt (price, quantity, name)
- FR36: User can delete incorrectly parsed line items
- FR37: User can add missing line items to a parsed receipt
- FR38: User can delete an entire receipt

### System Administration

- FR39: User can define and manage product categories
- FR40: User can view parsing/import history with status

## Non-Functional Requirements

### Performance

| Metric | Requirement | Rationale |
|--------|-------------|-----------|
| Page load | < 2 seconds | Dashboard should feel snappy |
| PDF parsing | < 5 seconds per receipt | Batch uploads need reasonable feedback |
| AI categorization | < 10 seconds per batch | Async is fine, but shouldn't block workflow |
| Dashboard queries | < 1 second | Analytics should feel instant |

### Data Integrity

| Requirement | Description |
|-------------|-------------|
| No silent data loss | All parsing errors must surface visibly, never silently drop data |
| Backup capability | Database should be easily exportable/backupable |
| Audit trail | Original parsed text retained for debugging |
| Transactional safety | Receipt imports are atomic (all-or-nothing) |

### External Dependencies

| Dependency | Handling |
|------------|----------|
| Gemini API | Graceful degradation if unavailable - queue for retry, don't block upload |
| Gemini API rate limits | Respect free tier limits, batch requests to stay within quota |
| API failure | User notified, manual categorization fallback available |

### Security (Minimal)

| Requirement | Description |
|-------------|-------------|
| Local/private hosting | No public exposure required |
| No authentication | Single-user tool, rely on host access control |
| Data encryption | Not required for personal spending data on private host |

