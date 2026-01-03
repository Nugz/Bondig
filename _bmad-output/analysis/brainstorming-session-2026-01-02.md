---
stepsCompleted: [1, 2]
inputDocuments: []
session_topic: 'Bondig - Personal grocery receipt scanner & spending tracker'
session_goals: 'Feature list + Technical solution approaches for receipt scanning and product recognition'
selected_approach: 'ai-recommended'
techniques_used: ['First Principles Thinking', 'SCAMPER Method', 'Cross-Pollination', 'Constraint Mapping']
ideas_generated: []
context_file: 'project-context-template.md'
---

# Brainstorming Session Results

**Facilitator:** Bram
**Date:** 2026-01-02
**Project:** Bondig

## Session Overview

**Topic:** Personal grocery receipt scanner and spending tracker - complete product vision with focus on technical challenges

**Goals:**
- Define comprehensive feature list for personal use
- Solve technical challenges around receipt OCR and product recognition
- Explore spending analytics and tracking capabilities

### Context Guidance

**Project Type:** Personal utility tool (single user)
**Key Advantages:**
- No multi-user complexity
- Can optimize for specific grocery stores
- Direct feedback loop (you are the user)
- Freedom to experiment

### Key Exploration Areas

- User Problems and Pain Points
- Feature Ideas and Capabilities
- Technical Approaches (especially OCR/recognition)
- User Experience
- Success Metrics

### Session Setup

Session configured for dual-focus brainstorming:
1. Product vision and feature ideation
2. Technical solution exploration for receipt scanning challenges

## Technique Selection

**Approach:** AI-Recommended Techniques
**Analysis Context:** Personal grocery receipt scanner with focus on features + technical solutions

**Recommended Techniques:**

1. **First Principles Thinking** (deep): Strip away assumptions to find Bondig's essential core - what do you *actually* need for personal use?
2. **SCAMPER Method** (structured): Systematic 7-lens feature exploration ensuring comprehensive coverage
3. **Cross-Pollination** (creative): Borrow proven solutions from banking apps, barcode scanners, document OCR
4. **Constraint Mapping** (structured): Map technical limitations and find pathways around them

**AI Rationale:** Dual-nature session requires both creative exploration (features) and analytical problem-solving (technical). This sequence moves from foundational clarity → systematic ideation → borrowed solutions → practical roadmap.

---

## Technique 1: First Principles Thinking

### Core Problem Identified
**Not** "I want to scan receipts" but rather: **"I want to understand WHY my grocery spending keeps increasing and WHERE I can optimize."**

### Three Root Questions
1. **Visibility Problem:** What am I actually spending a lot on?
2. **Diagnosis Problem:** What's causing increases - prices or behavior changes?
3. **Optimization Problem:** Where can I save with alternatives?

### Key Insight: Bondig is a "Grocery Spending Detective"
- **Reflection tool**, not real-time intervention tool
- Monthly analysis, not shopping assistant
- Batch processing receipts, not live scanning

### Success Moment Defined
Monthly report that shows:
- **Primary:** Category spending breakdown (where is money going?)
- **Secondary:** Price trends over time (are my regulars getting expensive?)

### UX Pattern
**Overview → Drill-down**
- See forest first (high-level categories)
- Zoom into trees (product-level detail) when something catches attention

### Input Reality
| Factor | Reality |
|--------|---------|
| Shopping frequency | 1-2 big weekly trips + occasional small ones |
| Store variety | Primarily ONE store (huge for parsing optimization!) |
| Receipt formats | Main store: Digital PDF / Secondary: Physical |
| Ideal input | Photo/PDF via email or drag-and-drop |

### Bondig Core Architecture (First Principles)
```
INPUT:  PDF upload / Photo upload / Email forward
           ↓
PROCESS: Parse receipts (optimized for main store format)
           ↓
STORE:  Products + Prices + Dates + Categories
           ↓
OUTPUT: Monthly report with category drill-down + price trends
```

### What Bondig Does NOT Need
- Real-time shopping assistant
- Mobile alerts while in-store
- Barcode scanning during shopping
- Complex notification systems
- Fancy mobile app

---

## Technique 2: SCAMPER Method

### S - Substitute
**Feature:** AI Auto-Categorization with Manual Override
- AI handles bulk categorization automatically
- User can correct/override any categorization
- System remembers user preferences over time

### C - Combine
**Decision:** Keep Bondig separate from shopping list
- User already uses Bring app and is happy with it
- No integration needed - stay focused on analysis

### A - Adapt
**Feature:** Recurring Purchase Tracker with Annualized Impact View
- Identify items bought regularly (weekly staples)
- Show annualized cost of recurring purchases
- Highlight yearly impact of small price increases
- Example: "Your 15 weekly staples increased €2.80/week = €145/year in inflation"

### M - Modify
**Feature:** Flexible Time Range Selection (Dashboard-style)
- User-selectable time windows (this month, 3 months, year, custom)
- Comparison window selection (vs last month, vs same period last year)
- Full control over analysis parameters

**Anti-features:**
- No auto-import from email (manual upload is fine)
- No oversimplified summaries
- No proactive alerts

### P - Put to Other Uses
**Feature:** Single Monthly Grocery Budget with Tracking
- Set one total monthly budget (e.g., €400)
- Track progress through the month
- Review actual vs budget at month end
- Category drill-down is for understanding, not budget constraints

### E - Eliminate
**Confirmed lean scope:**
- No real-time shopping features
- No mobile app (web-based)
- No shopping list integration
- No auto-import complexity
- No proactive alerts/notifications
- No category-level budgets

### R - Reverse
**Future consideration:** Monitor if Albert Heijn exposes structured purchase data (API/export) that could bypass OCR. For now, PDF receipts are the path.

---

## Technique 3: Cross-Pollination

### Patterns Stolen from Other Domains

**From Banking Apps (Bunq, ING):**
- Auto-categorize → User corrects → System learns
- Battle-tested pattern, no need to invent

**From Document Scanners (Adobe Scan, Google Lens):**
- Use existing OCR libraries, don't build your own
- For photos: Google Vision API, Tesseract, or Apple Vision
- For PDFs: Simple text extraction (PyPDF, pdf.js)

### Real Receipt Analysis: Albert Heijn PDF

**MAJOR FINDING: AH receipts are TEXT-BASED PDFs!**
- No OCR needed for AH receipts
- Text is embedded and directly extractable
- Simple PDF text extraction libraries will work

**Receipt Structure (very consistent):**
```
AANTAL  OMSCHRIJVING        PRIJS   BEDRAG
  4     PAPRIKA             0,89    3,56 B
  1     WINTERPEEN                  0,35
  1     AH BANANEN                  1,39
```

| Column | Meaning | Notes |
|--------|---------|-------|
| AANTAL | Quantity | Always present |
| OMSCHRIJVING | Product name | Abbreviated but readable |
| PRIJS | Unit price | Only shown if qty > 1 |
| BEDRAG | Total price | Always present |
| B flag | Bonus item | Indicates discounted item |

**Clear Sections in Receipt:**
1. Products - Main purchase lines
2. +STATIEGELD - Deposit lines (easy to filter)
3. BONUS lines - Discounts applied
4. UW VOORDEEL - Total savings
5. TOTAAL - Final amount
6. Date/Time - e.g., "29-12-2025 11:56"

**Parsing Difficulty: EASY** ✅
- Regex pattern: `^\s*(\d+)\s+(.+?)\s+([\d,]+)?\s+([\d,]+)\s*(B)?$`
- Consistent format makes parsing straightforward

**The Real Challenge: Product Recognition**
- Parsing is easy, categorization is hard
- Examples: "BEEMSTER" = cheese brand, "QUAK CRUESLI" = Quaker cereals
- Solution: AI categorization with manual override + learning over time

### Tech Stack Direction
- **Framework:** Laravel (user preference)
- **PDF Extraction:** PHP libraries (e.g., smalot/pdfparser, spatie/pdf-to-text)
- **Photo OCR:** External API (Google Vision) for non-AH receipts
- **Categorization:** LLM/AI for initial guess, user corrections stored

---

## Technique 4: Constraint Mapping

### Technical Constraints

| Constraint | Severity | Pathway |
|------------|----------|---------|
| AH PDF parsing | ✅ SOLVED | Text-based, easy extraction |
| Other store receipts (photo) | Medium | OCR API - "best effort" |
| Product → Category mapping | Medium | AI + manual override + learning |
| Matching same product over time | Low | Exact string match + manual merge |
| Price tracking accuracy | Low | Same name = same product |

**Decision:** Manual product merging is acceptable - no complex fuzzy matching needed.

### Data Constraints

| Constraint | Severity | Pathway |
|------------|----------|---------|
| Need history for trends | ✅ SOLVED | 12 months of AH receipts available! |
| Missing receipts | Low | Personal use = you control completeness |
| Category accuracy | Low | Gets better with corrections over time |

**Advantage:** 12 months historical data means instant value from day one.

### User Experience Constraints

| Constraint | Severity | Pathway |
|------------|----------|---------|
| Upload friction | Low | Drag-and-drop, already acceptable |
| Categorization review | Low | Batch review weekly |
| Learning curve | None | Building for yourself |

**UX Decision:** Instant feedback on upload + batch mode option
- Single: "Parsed 34 items, €144.81, 3 need categorization"
- Batch: Upload multiple, review together

### Scope/Effort: MVP Definition

**MVP Scope (Build First):**
- ✅ Upload AH PDF receipts (single + batch)
- ✅ Parse and extract products/prices/dates
- ✅ AI auto-categorization with manual override
- ✅ Dashboard: spending by category (drill-down)
- ✅ Dashboard: flexible time range selection
- ✅ Basic price tracking (same product over time)
- ✅ Single monthly budget + progress
- ✅ Manual product merge

**V2 Scope (After MVP):**
- ⏳ Photo OCR for other stores
- ⏳ Recurring purchase detection
- ⏳ Annualized impact view
- ⏳ Year-over-year comparisons
- ⏳ "Cheaper alternatives" suggestions

---

## Session Summary

### What is Bondig?
**A personal "Grocery Spending Detective"** - a monthly reflection tool that helps you understand WHY grocery spending increases and WHERE to optimize.

### Core Value Proposition
Upload receipts → Get insights on spending patterns, price trends, and budget progress.

### Key Decisions Made

| Decision | Choice |
|----------|--------|
| Tool type | Reflection/analysis, NOT real-time assistant |
| Primary store | Albert Heijn (PDF receipts, text-based) |
| Input method | Upload PDF / photo, drag-and-drop or email |
| Output format | Monthly dashboard with drill-down |
| Budget model | Single total monthly budget |
| Categorization | AI auto + manual override + learning |
| Product matching | Exact string + manual merge |
| Tech stack | Laravel |

### Features Summary

**MVP Features:**
1. AH PDF receipt parsing (single + batch upload)
2. AI auto-categorization with manual override
3. Dashboard: category spending with drill-down
4. Dashboard: flexible time range + comparison windows
5. Price tracking over time
6. Monthly budget tracking
7. Manual product merge

**Anti-Features (Intentionally Excluded):**
- No real-time shopping features
- No mobile app
- No shopping list integration
- No proactive alerts
- No category-level budgets

### Technical Insights
- AH receipts are TEXT-BASED PDFs (no OCR needed!)
- Consistent format makes parsing straightforward
- 12 months historical data available for bootstrap
- Main challenge is product → category mapping (solved with AI + corrections)

### Next Steps
1. **Product Brief** - Document the product vision formally
2. **Research** - Evaluate Laravel PDF libraries, LLM APIs for categorization
3. **PRD** - Detailed requirements document
4. **Architecture** - Technical design
5. **Build MVP** - Start with receipt parser + basic dashboard

---

**Session completed: 2026-01-02**
**Techniques used:** First Principles Thinking, SCAMPER Method, Cross-Pollination, Constraint Mapping
**Facilitator:** Mary (Business Analyst)

