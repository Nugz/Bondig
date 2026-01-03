---
stepsCompleted: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14]
status: complete
inputDocuments:
  - "_bmad-output/planning-artifacts/product-brief-bondig-2026-01-02.md"
  - "_bmad-output/planning-artifacts/prd.md"
  - "_bmad-output/project-context.md"
  - "_bmad-output/planning-artifacts/architecture.md"
  - "_bmad-output/planning-artifacts/research/technical-bondig-pdf-ai-research-2026-01-02.md"
  - "_bmad-output/analysis/brainstorming-session-2026-01-02.md"
project_name: "Bondig"
user_name: "Bram"
date: "2026-01-02"
---

# UX Design Specification: Bondig

**Author:** Bram
**Date:** 2026-01-02

---

## Executive Summary

### Project Vision

Bondig is a personal "Grocery Spending Detective" - a monthly reflection tool that transforms Albert Heijn receipt data into actionable spending insights. Unlike budget apps that only show merchant-level spending, Bondig provides product-level visibility to answer: *Why is spending increasing? Where is the money going? Where can I optimize?*

**Design Philosophy:** Reflection tool, not real-time assistant. Batch processing, not constant engagement. Power user dashboard, not oversimplified summaries.

**Core UX Principle:** Automate the tedious, surface what matters, keep the user in control.

### Target Users

**Primary User:** Bram - Solo household grocery manager

| Attribute | Details |
|-----------|---------|
| Technical level | Intermediate - comfortable building own tools |
| Primary store | Albert Heijn (90%+ of purchases) |
| Shopping pattern | 1-2 big weekly trips + occasional small ones |
| Receipt access | Digital PDFs from AH app/email |
| Usage pattern | Weekly upload (~5 min), monthly deep-dive reflection |

**User Motivations:**
- Understand where grocery money actually goes
- Identify price increases on regular purchases
- Set and track realistic grocery budgets
- Find optimization opportunities

**Success Moment:** Monthly report that reveals spending patterns and price trends, enabling informed decisions that lead to 15% spending reduction.

### Key Design Challenges

| Challenge | Context |
|-----------|---------|
| Batch workflow friction | Weekly uploads need to feel effortless (<5 min). If it's tedious, the habit won't stick. |
| Categorization review UX | AI will make mistakes. How do we surface items needing attention without overwhelming? |
| Information density balance | Power user dashboard, but not overwhelming. Clear hierarchy: forest first, then trees. |
| Error visibility without alarm | Parsing errors must surface visibly, but shouldn't create anxiety or erode trust. |
| Price trend clarity | Show price changes in a way that's instantly understandable and actionable. |

### Design Opportunities

| Opportunity | Potential Impact |
|-------------|------------------|
| "Aha moment" dashboard | First view should immediately reveal surprising insights |
| Smart defaults with escape hatches | AI handles bulk work, user corrects exceptions, system learns |
| Time comparison as core interaction | "This month vs last month" makes trends immediately visible |
| Annualized impact framing | Show small increases as yearly impact (€0.50/week = €26/year) |
| Single-purpose clarity | No feature creep - do one thing exceptionally well |

## Core User Experience

### Defining Experience

**Primary Interaction Rhythm:**

| Frequency | Action | Time Investment |
|-----------|--------|-----------------|
| Weekly | Upload receipts + quick category review | ~5 minutes |
| Monthly | Dashboard deep-dive reflection | 15-30 minutes |

**The Core Loop:** Upload receipt → Parse → Categorize → View insights

**Critical Success Factor:** The weekly upload flow must feel effortless. If it's tedious, the habit breaks and data stops flowing. Everything else (insights, trends, budgets) depends on consistent data input.

### Platform Strategy

| Aspect | Decision | Rationale |
|--------|----------|-----------|
| Platform | Web application (Laravel MPA) | Simplicity over complexity |
| Primary input | Mouse/keyboard (desktop) | Weekly batch workflow suits desktop |
| Mobile | Basic viewing acceptable | Not primary use case |
| Offline | Not required | Always-connected use case |
| Key capability | Drag-and-drop file upload | Core to frictionless input |

### Effortless Interactions

**User Actions That Must Feel Natural:**

| Interaction | Target Experience |
|-------------|-------------------|
| Receipt upload | Drag, drop, done - instant feedback |
| Category confirmation | One click to confirm AI suggestion |
| Dashboard navigation | Click category → see products (obvious drill-down) |
| Time range selection | Instant comparison, no page reloads |
| Budget check | Glanceable - know status in 2 seconds |

**Automatic Behaviors (No User Action Required):**
- PDF parsing and text extraction
- AI product categorization (first-pass)
- Product matching and price tracking over time
- Budget progress calculation

### Critical Success Moments

| Moment | Success Indicator |
|--------|-------------------|
| First upload | "It actually parsed everything correctly" |
| First month review | "So THAT'S where the money goes" - the aha moment |
| Price discovery | "Butter is 20% more expensive than 3 months ago" |
| Budget win | "I came in under budget by making informed swaps" |

**Make-or-Break Flow:** First-time historical data load. If 12 months of receipts import cleanly with minimal manual correction, trust is established immediately.

### Experience Principles

| # | Principle | Application |
|---|-----------|-------------|
| 1 | **Friction-free input** | Upload is drag-drop-done. Every extra click is a reason to skip. |
| 2 | **AI does the work, you have the control** | Automate categorization, but corrections are instant and remembered. |
| 3 | **Overview first, details on demand** | Dashboard shows the forest. Click to see trees. Never overwhelm. |
| 4 | **Insight over data** | Surface what changed, what matters, what to do - not just numbers. |
| 5 | **Trust through transparency** | Show confidence levels. Surface errors visibly. Never hide problems. |

## Desired Emotional Response

### Primary Emotional Goals

**Core Feeling:** In control and informed about grocery spending - the opposite of financial blindness.

| Moment | Target Emotion |
|--------|----------------|
| First upload success | Relief + Trust - "It actually works" |
| First month dashboard | Revelation - "So THAT'S where the money goes" |
| Spotting a price trend | Empowered - "I caught that before it cost me more" |
| Hitting budget target | Accomplishment - "I'm in control" |

**Differentiation:** Banking apps create anxiety ("you spent too much"). Bondig creates curiosity and empowerment ("here's what's happening, here's what you can do").

### Emotional Journey Mapping

| Stage | Desired Feeling | Avoid |
|-------|-----------------|-------|
| Discovery/First Use | Curious, optimistic | Overwhelmed, skeptical |
| Upload & Parse | Confident, smooth | Anxious, uncertain |
| Category Review | Quick, easy | Tedious, annoying |
| Dashboard Exploration | Insightful, in control | Confused, lost |
| Error/Problem | Informed, able to fix | Frustrated, helpless |
| Return Visit | Familiar, efficient | Forgotten how it works |

### Micro-Emotions

| Critical State | Why It Matters |
|----------------|----------------|
| Confidence over Confusion | Clear UI signals what to do next |
| Trust over Skepticism | Accurate parsing = trust in all numbers |
| Accomplishment over Frustration | Quick wins in the weekly flow |
| Curiosity over Anxiety | Dashboard invites exploration, not judgment |

### Design Implications

**Emotion-to-Design Connections:**

| Emotion | UX Approach |
|---------|-------------|
| Confidence | Clear visual hierarchy, obvious next actions, no dead ends |
| Trust | Show parsing confidence, display original text, transparent errors |
| Control | Easy overrides, undo capability, corrections remembered |
| Curiosity | Clickable insights, inviting drill-downs |
| Accomplishment | Progress indicators, budget celebrations, improvements highlighted |
| Calm | Clean layout, no red alarms for non-critical issues |

**Emotions to Prevent:**

| Negative Emotion | Prevention Strategy |
|------------------|---------------------|
| Overwhelm | Progressive disclosure, overview-first design |
| Anxiety | No judgmental language, insights not warnings |
| Tedium | Batch actions, smart defaults, minimal clicks |
| Distrust | Never hide errors, show data source |
| Guilt | Neutral language, focus on patterns not judgment |

### Emotional Design Principles

| # | Principle | Application |
|---|-----------|-------------|
| 1 | **Reveal, don't judge** | Show spending patterns without moralizing |
| 2 | **Invite exploration** | Make drill-downs rewarding, not required |
| 3 | **Celebrate small wins** | Acknowledge budget progress and improvements |
| 4 | **Fail gracefully** | Errors are opportunities, not dead ends |
| 5 | **Build trust incrementally** | Accuracy in small things creates trust for insights |

## UX Pattern Analysis & Inspiration

### Inspiring Products Analysis

#### Banking Apps (Bunq/Monzo)
**Relevant for:** Categorization workflow

| What They Do Well | Application to Bondig |
|-------------------|----------------------|
| Auto-categorize transactions instantly | AI categorizes products on upload |
| Simple tap to change category | One-click category override |
| Remember user corrections | System learns from your fixes |
| Colorful category badges | Visual category distinction |
| Non-judgmental tone | "Here's your spending" not "You overspent" |

**Key Pattern:** Correction feels like a 2-second fix, not a chore.

#### Dropbox
**Relevant for:** File upload experience

| What They Do Well | Application to Bondig |
|-------------------|----------------------|
| Drag anywhere on the page | Large drop target for receipts |
| Instant visual feedback | Show file accepted immediately |
| Progress indication | "Parsing... 34 items found" |
| Batch upload naturally | Multiple files, one action |
| Non-blocking errors | "3 of 5 uploaded" not total failure |

**Key Pattern:** Upload feels instantaneous even when processing takes time.

#### YNAB (You Need A Budget)
**Relevant for:** Dashboard and budget tracking

| What They Do Well | Application to Bondig |
|-------------------|----------------------|
| Category-first view | See spending by category at a glance |
| Click to drill down | Category → individual transactions |
| Budget vs actual | Simple progress bars |
| Time period selection | Easy month switching |
| Clean, uncluttered design | Data density without overwhelm |

**Key Pattern:** Complex financial data made scannable in seconds.

### Transferable UX Patterns

| Category | Pattern | Application to Bondig |
|----------|---------|----------------------|
| Upload | Large drag-drop zone | Full-width drop area on upload page |
| Upload | Instant acknowledgment | "File received" before parsing completes |
| Upload | Non-blocking progress | Show items parsed as they process |
| Categorization | Inline editing | Change category without opening modal |
| Categorization | Smart defaults | AI suggestion pre-selected, one click to confirm |
| Categorization | Badge colors | Consistent category colors across all views |
| Dashboard | Card-based layout | Each category as a clickable card |
| Dashboard | Progressive disclosure | Summary → click → detail |
| Budget | Progress bar | Visual budget remaining at a glance |
| Trends | Sparklines | Tiny trend indicators next to numbers |

### Anti-Patterns to Avoid

| Anti-Pattern | Why It Fails | Bondig Alternative |
|--------------|--------------|-------------------|
| Modal overload | Interrupts flow, feels heavy | Inline editing, slide-out panels |
| Red alert colors for normal states | Creates anxiety | Reserve red for actual errors only |
| Requiring categorization before viewing | Blocks the "aha moment" | Show dashboard immediately, flag uncategorized |
| Complex multi-step wizards | Feels tedious for repeat tasks | Single-page flows |
| Hidden errors | Erodes trust | Always surface issues visibly |
| Judgmental language | Creates guilt | Neutral, observational tone |

### Design Inspiration Strategy

**Adopt Directly:**
- Drag-and-drop upload with instant feedback (Dropbox)
- One-click category override with color badges (Bunq)
- Category cards with drill-down (YNAB)
- Progress bars for budget tracking (YNAB)

**Adapt for Bondig:**
- Banking app categorization → adapt for products, not transactions
- YNAB time periods → simplify to month picker + comparison
- Dropbox batch upload → add parsing progress specific to receipts

**Avoid:**
- Complex onboarding wizards (not needed for single user)
- Mobile-first patterns (desktop is primary)
- Real-time notifications (reflection tool, not alerts)

## Design System Foundation

### Design System Choice

**Primary Framework:** Tailwind CSS + daisyUI

| Layer | Technology | Purpose |
|-------|------------|---------|
| Utility Framework | Tailwind CSS | Low-level styling, custom layouts |
| Component Library | daisyUI | Pre-built components, theming |
| Interactivity | Alpine.js | Dropdowns, modals, toggles |
| Data Visualization | Chart.js | Spending charts, trends |

### Rationale for Selection

| Factor | Decision Driver |
|--------|-----------------|
| Cost | Free & open source - appropriate for personal project |
| Speed | Pre-built components accelerate development |
| Flexibility | Tailwind underneath allows full customization |
| Simplicity | Semantic classes (`btn`, `card`) keep HTML readable |
| Compatibility | Works seamlessly with Laravel Blade + Alpine.js |
| Theming | Easy color customization for category badges |

### Implementation Approach

**Use daisyUI Components For:**
- Buttons (primary actions, category confirmation)
- Cards (category cards on dashboard, receipt cards)
- Badges (category labels with colors)
- Tables (receipt line items, product lists)
- Progress bars (budget tracking)
- Dropdowns (category selection, time range picker)
- Alerts (flash messages: success, warning, error)
- Form inputs (manual entry, budget setting)

**Build Custom Components For:**
- Upload drop zone (drag-and-drop with progress)
- Dashboard grid layout (category cards arrangement)
- Price trend sparklines (inline Chart.js)
- Category review workflow (batch confirmation UI)
- Receipt parser result view (parsed items with confidence)

### Customization Strategy

**Theme Configuration:**
- Define category colors as CSS custom properties
- Map to daisyUI theme for consistent badge colors
- Neutral base palette (avoid anxiety-inducing colors)
- Reserve semantic colors: green (success), amber (warning), red (errors only)

**Component Patterns:**
- Consistent card styling across all views
- Unified button hierarchy (primary, secondary, ghost)
- Standard spacing scale from Tailwind
- Responsive breakpoints for basic mobile viewing

## Visual Design & Interactions

### Defining Experience

**Core Interaction:** "Drop receipts, see where money goes"

The defining experience users will describe: *"I just drop my AH receipts and it shows me exactly what I'm spending on groceries."*

**The Magic:** Zero-effort input → Immediate insight

| Success Factor | Target |
|----------------|--------|
| Time to insight | <3 seconds from drop to results |
| Parse accuracy | 90%+ products correct on first try |
| Cognitive load | User understands immediately |
| Trust building | Numbers feel accurate without verification |

### User Mental Model

**Current Solutions & Pain Points:**

| Current Approach | Pain Point |
|------------------|------------|
| Bank app categories | Only shows merchant total, not product breakdown |
| Spreadsheet tracking | Manual data entry too tedious to maintain |
| Mental estimation | No real visibility into spending |
| Ignoring it | No actionable insight |

**User Expectations:**
- Upload should be drag-and-drop (Dropbox mental model)
- Parsing should feel instant
- Categories should auto-assign sensibly
- Dashboard should reveal surprises

### Success Criteria

| Criteria | Success Indicator |
|----------|-------------------|
| Speed | File dropped → Results in <3 seconds |
| Accuracy | 90%+ products correctly parsed |
| Clarity | Immediate understanding of results |
| Actionability | At least one "I didn't know that" insight |
| Trust | User believes numbers without verifying |

**"It just works" moment:** Drop PDF → "34 items parsed, €127.45 total" appears immediately with auto-assigned categories.

### Pattern Analysis

**Approach:** Established patterns, executed perfectly

| Pattern | Source | Application |
|---------|--------|-------------|
| Upload | Dropbox | Drag-and-drop with instant feedback |
| Categorization | Banking apps | Auto-categorize + one-click override |
| Dashboard | YNAB | Category cards with drill-down |

**Bondig's Innovation:** Product-level grocery tracking with price trends. Familiar patterns, novel insight.

### Experience Mechanics

#### Upload Flow

**1. Initiation:**
- Large drop zone on dashboard or dedicated page
- Visual: dashed border, icon, "Drop receipts here"
- Alternative: click to browse files
- Batch: multi-file drop supported

**2. Interaction:**

| Step | User Action | System Response |
|------|-------------|-----------------|
| Drop | Drag PDF(s) to zone | Zone highlights, "Release to upload" |
| Release | Let go | "Processing..." with file count |
| Wait | Watch progress | "Parsing... 24 items found" |
| Review | Scan results | Summary: items, total, categories to review |

**3. Feedback:**

| State | Visual |
|-------|--------|
| Success | Green check, "34 items, €127.45" |
| Partial | Amber, "3 items need review" |
| Error | Red on specific file, explanation |
| Progress | Live counter, running total |

**4. Completion:**
- Summary card with totals
- Next action: "Review categories" or "View dashboard"
- Auto-save (no save button)
- Recent uploads accessible from sidebar

## Visual Design Foundation

### Color System

**Philosophy:** Calm and trustworthy, not anxiety-inducing. Numbers are neutral, not scary.

#### Base Palette

| Role | Color | Hex | Usage |
|------|-------|-----|-------|
| Primary | Teal 600 | `#0D9488` | Primary actions, key metrics, brand accent |
| Secondary | Slate 600 | `#475569` | Secondary text, borders, subtle UI |
| Background | Stone 50 | `#FAFAF9` | Page background (warm white) |
| Surface | White | `#FFFFFF` | Cards, panels |
| Text | Slate 800 | `#1E293B` | Primary text |
| Muted | Slate 500 | `#64748B` | Secondary text, labels |

#### Semantic Colors

| State | Color | Hex | Usage |
|-------|-------|-----|-------|
| Success | Emerald 500 | `#10B981` | Budget on track, successful parse |
| Warning | Amber 500 | `#F59E0B` | Items need review, approaching budget |
| Error | Rose 500 | `#F43F5E` | Parse failures, actual errors only |
| Info | Sky 500 | `#0EA5E9` | Tips, neutral information |

#### Category Colors

| Category | Color | Hex |
|----------|-------|-----|
| Produce | Green 500 | `#22C55E` |
| Dairy | Blue 500 | `#3B82F6` |
| Meat/Fish | Red 500 | `#EF4444` |
| Bakery | Amber 500 | `#F59E0B` |
| Beverages | Cyan 500 | `#06B6D4` |
| Snacks | Purple 500 | `#A855F7` |
| Household | Slate 500 | `#64748B` |
| Other | Stone 500 | `#78716C` |

### Typography System

**Font Family:** Inter (with system font fallback)

| Element | Size | Weight | Usage |
|---------|------|--------|-------|
| H1 | 30px | 600 | Page titles |
| H2 | 24px | 600 | Section headers |
| H3 | 20px | 600 | Card titles |
| H4 | 16px | 600 | Subsections |
| Body | 16px | 400 | Primary content |
| Small | 14px | 400 | Secondary info |
| Tiny | 12px | 400 | Timestamps, meta |
| Numbers | 16px | 500 | Financial data |

### Spacing & Layout Foundation

**Base Unit:** 4px (Tailwind default)

| Scale | Value | Usage |
|-------|-------|-------|
| xs | 4px | Tight groupings |
| sm | 8px | Related elements |
| md | 16px | Standard spacing |
| lg | 24px | Section separation |
| xl | 32px | Major sections |
| 2xl | 48px | Page margins |

**Layout Principles:**
- Card-based UI with consistent 16-24px padding
- 12-column grid, 3-4 cards per row on dashboard
- Max content width: 1280px
- Generous whitespace between sections

### Accessibility Considerations

| Requirement | Implementation |
|-------------|----------------|
| Contrast | All text meets WCAG AA (4.5:1 minimum) |
| Focus states | Visible focus rings on interactive elements |
| Color independence | Icons/text accompany color indicators |
| Minimum font size | 14px, preferably 16px for body |
| Touch targets | Minimum 44px for interactive elements |

## Design Direction Decision

### Design Directions Explored

Four key screens were evaluated with multiple layout approaches:

| Screen | Directions Explored |
|--------|---------------------|
| Dashboard | Card Grid, Summary+List, Chart Focus, Timeline Focus |
| Upload Flow | Full Page, Dashboard Inline, Modal, Slide-out Panel |
| Category Detail | Table Layout, Product Cards |
| Navigation | Top Nav, Sidebar, Minimal |

Interactive mockups generated at: `_bmad-output/planning-artifacts/ux-design-directions.html`

### Chosen Direction

| Screen | Choice | Pattern |
|--------|--------|---------|
| Dashboard | Card Grid | Category cards in responsive grid, clickable for drill-down |
| Upload | Full Page | Dedicated page with large drop zone, progress feedback |
| Category Detail | Table Layout | Sortable table with inline category badges |
| Navigation | Top Navigation | Horizontal nav bar, simple and familiar |

### Design Rationale

| Decision | Rationale |
|----------|-----------|
| Card Grid Dashboard | Matches YNAB mental model, clear click targets, scannable at glance |
| Full Page Upload | Large drop zone reduces friction, Dropbox-like familiarity, focused attention |
| Table Detail View | Dense information display, sortable, power-user friendly |
| Top Navigation | Maximum content width, simple for 4-5 nav items, familiar pattern |

**Guiding Principle:** Prioritize simplicity and familiarity over novelty. Use established patterns users already understand (YNAB, Dropbox, banking apps) and execute them well.

### Implementation Approach

**Dashboard Layout:**
- Header with month selector and budget progress
- 4-column grid of category cards (responsive to 2-col on smaller screens)
- Each card shows: category badge, total, item count, trend indicator
- Click card → navigate to category detail

**Upload Page:**
- Full-width drop zone with dashed border
- Icon and "Drop receipts here" text
- Progress feedback during parsing
- Summary card on completion with next actions

**Category Detail:**
- Category header with total and trend
- Sortable table: Product, Qty, Price, Trend
- Inline category badge editable
- Back navigation to dashboard

**Navigation Structure:**
- Logo left, nav items center/right
- Items: Dashboard, Upload, Products, Settings
- Active state with teal underline
- User avatar/settings on far right

## User Journey Flows

### Weekly Upload Flow

**Goal:** Add new receipts with minimal friction (<5 minutes)

**Flow:**
1. Dashboard → Click "Upload" → Upload Page
2. Drag PDF(s) to drop zone → Instant "Processing..." feedback
3. "Parsing... 24 items found" → Progress visible
4. Success card: "34 items, €127.45" + "3 items need review"
5. Choose: [Review Categories] or [View Dashboard]
6. If review needed → Category Review Page → One-click assignments → Done

**Critical Path:** Drop → Parse → Confirm → Dashboard (4 steps max)

### Monthly Reflection Flow

**Goal:** Understand spending patterns, check budget (15-30 minutes)

**Flow:**
1. Dashboard → View month summary (total, budget progress)
2. Scan category cards → Spot anomalies (e.g., "Snacks +25%")
3. Click category → Category Detail (products, prices, trends)
4. Click product → Product Detail (price history, frequency)
5. Compare months via dropdown → Instant visual comparison
6. Check budget status → Progress bar with remaining amount

**Key Interaction:** Overview → Drill-down → Insight

### First-Time Setup Flow

**Goal:** Import history, establish baseline, create "aha moment"

**Flow:**
1. Welcome message → "Let's import your receipt history"
2. Upload Page (batch mode) → Drop 50+ PDFs
3. Progress: "Processing 52 receipts... [████████░░] 38/52"
4. Import complete summary: receipts, items, total parsed
5. Category review (batch) → Confirm grouped suggestions
6. Set budget → Suggested based on history average
7. Dashboard → Full history visible, insights surfaced

**Critical Success:** Trust established through accurate parsing

### Category Correction Flow

**Goal:** Fix miscategorized items quickly

**Flow:**
1. Any product list → Click category badge
2. Dropdown with category options appears
3. Select new category
4. Prompt: "Apply to all 'Cruesli'?" → [Just this one] / [All 8]
5. Badge updates instantly → Toast confirmation
6. System remembers for future imports

**Key Pattern:** Inline edit, batch option, remembered

### Journey Patterns

**Navigation Pattern:**
- Overview first (Dashboard)
- Click to drill down (Category → Product)
- Clear back navigation
- Breadcrumbs for deep navigation

**Feedback Pattern:**
- Instant visual response to all actions
- Progress indicators for async operations
- Success/warning/error states clearly distinguished
- Toast notifications for confirmations

**Decision Pattern:**
- Smart defaults pre-selected
- One-click to confirm default
- Easy override when needed
- Batch actions offered when relevant

### Flow Optimization Principles

| Principle | Application |
|-----------|-------------|
| Minimize steps to value | Upload → Dashboard in 4 steps or less |
| Show progress | Live counters during parsing |
| Fail gracefully | Partial success OK, surface errors per-file |
| Remember preferences | Category corrections applied to future imports |
| Offer shortcuts | "Confirm all" for batch operations |
| Clear next actions | Always show obvious next step |

## Component Strategy

### Design System Components (daisyUI)

| Component | Usage | daisyUI Class |
|-----------|-------|---------------|
| Buttons | Primary actions, confirmations | `btn btn-primary`, `btn-secondary`, `btn-ghost` |
| Cards | Category cards, receipt cards | `card`, `card-body` |
| Badges | Category labels | `badge badge-{color}` |
| Tables | Product lists, receipt items | `table`, `table-zebra` |
| Progress | Budget tracking | `progress progress-primary` |
| Dropdowns | Category select, month picker | `dropdown`, `select` |
| Alerts | Flash messages | `alert alert-{type}` |
| Form inputs | Budget setting, search | `input`, `input-bordered` |
| Modal | Confirmation dialogs | `modal` |
| Toast | Notifications | `toast` |

**Coverage:** ~80% of UI needs covered by daisyUI components.

### Custom Components

#### Upload Drop Zone

**Purpose:** Receive PDF files via drag-and-drop with visual feedback

**States:**
| State | Visual |
|-------|--------|
| Default | Dashed border (`border-slate-300`), muted icon/text |
| Drag over | Teal border (`border-teal-400`), light teal bg (`bg-teal-50`) |
| Processing | Spinner icon, "Processing..." text, progress counter |
| Success | Green check icon, item count and total |
| Error | Red border, error message per file |

**Anatomy:** Container → Icon → Primary text → Secondary text → File type hint

**Accessibility:** Keyboard accessible, role="button", aria-label for screen readers

#### Category Card

**Purpose:** Display category spending summary, invite drill-down

**Anatomy:**
- Category badge (top-left, colored)
- Item count (top-right, muted)
- Total amount (center, large bold)
- Percentage of total (below amount)
- Trend indicator (bottom, colored +/-%)

**States:** Default (white), Hover (shadow-md), Focus (teal ring)

**Interactions:** Click navigates to category detail

#### Parse Result Summary

**Purpose:** Show upload results with clear next actions

**Anatomy:**
- Status icon (check/warning circle)
- Summary: "{n} items, €{total}"
- Warning badge (if items need review)
- Action buttons: [Review Categories] [View Dashboard]

**Variants:** Full success, Partial success (with warnings), Error

#### Price Sparkline

**Purpose:** Show 6-point price trend inline with product data

**Dimensions:** 48px × 16px

**Colors:** Teal (stable), Rose (increasing >10%), Emerald (decreasing >10%)

**Implementation:** Chart.js line chart, minimal config, no axes/labels

#### Category Review List

**Purpose:** Batch review items needing category assignment

**Anatomy:**
- Product name (left)
- Category badge dropdown (center)
- Confidence indicator (if <80%)
- Batch actions header: [Confirm All] [Skip All]

**Interactions:** Click badge → category dropdown, "Apply to all similar" checkbox

### Component Implementation Strategy

**Build Approach:**
- Use daisyUI components as foundation
- Custom components built with Tailwind utilities
- Follow daisyUI patterns for consistency (sizing, colors, states)
- Alpine.js for interactive behaviors (dropdowns, drag-drop)

**Styling Tokens:**
- Colors: Use Tailwind color palette (teal, slate, emerald, amber, rose)
- Spacing: Tailwind scale (p-4, gap-4, etc.)
- Border radius: `rounded-lg` standard, `rounded-xl` for cards
- Shadows: `shadow-sm` default, `shadow-md` hover

### Implementation Roadmap

**Phase 1 - Core (MVP):**
- Upload Drop Zone (critical path)
- Category Card (dashboard)
- Parse Result Summary (upload feedback)

**Phase 2 - Enhancement:**
- Category Review List (batch workflow)
- Price Sparkline (trend visibility)

**Phase 3 - Polish:**
- Advanced filtering components
- Export/report components

## UX Consistency Patterns

### Button Hierarchy

| Level | Style | Usage | Class |
|-------|-------|-------|-------|
| Primary | Teal solid | Main action per screen | `btn btn-primary` |
| Secondary | Gray solid | Alternative actions | `btn btn-secondary` |
| Ghost | Text only | Cancel, skip, optional | `btn btn-ghost` |
| Danger | Red solid | Destructive actions | `btn btn-error` |

**Rules:**
- ONE primary button per screen/card maximum
- Primary button positioned right in button groups
- Ghost buttons for dismissing/canceling
- Always provide cancel/back option

### Feedback Patterns

#### Success
- Color: Emerald (`bg-emerald-50`, `text-emerald-600`)
- Icon: Checkmark circle
- Toast auto-dismiss: 4 seconds
- Usage: Parse success, save confirmations

#### Warning
- Color: Amber (`bg-amber-50`, `text-amber-600`)
- Icon: Exclamation triangle
- Behavior: Persist until addressed
- Usage: Items need review, approaching budget

#### Error
- Color: Rose (`bg-rose-50`, `text-rose-600`)
- Icon: X circle
- Behavior: Persist until fixed/dismissed
- Usage: Parse failed, invalid file, save error

#### Info
- Color: Sky (`bg-sky-50`, `text-sky-600`)
- Icon: Info circle
- Toast auto-dismiss: 4 seconds
- Usage: Tips, suggestions, neutral info

### Loading States

| Scenario | Pattern |
|----------|---------|
| Page load | Skeleton placeholders for cards/tables |
| Button action | Spinner replaces text, button disabled |
| File upload | Progress bar with % + item count |
| Background save | Subtle indicator, non-blocking |

**Rules:**
- Feedback required if blocking >300ms
- Show progress for operations >2s
- Allow cancel for long operations

### Empty States

| Scenario | Message | Action |
|----------|---------|--------|
| No receipts | "Upload your first receipt" | Upload button |
| Empty category | "No products in this category" | Back link |
| No budget | "Set a budget to track progress" | Set budget button |
| No search results | "No products match" | Clear search link |

**Rules:**
- Explain why empty
- Provide clear next action
- Friendly, encouraging tone

### Navigation Patterns

| Pattern | Behavior |
|---------|----------|
| Breadcrumbs | Show for depth >1 |
| Back button | Always visible when drilled down |
| Active state | Teal underline on current nav |
| Month picker | Dropdown, instant update |

**Rules:**
- User always knows location
- One click back/up
- No dead ends

### Inline Edit Pattern

| Step | Behavior |
|------|----------|
| Trigger | Click editable element |
| Edit mode | Dropdown appears immediately |
| Confirm | Select = auto-save |
| Feedback | Brief success indicator |
| Batch | "Apply to all" checkbox offered |

**Rules:**
- No separate edit mode
- Save on selection
- Batch actions for repeatable items

## Responsive Design & Accessibility

### Responsive Strategy

**Approach:** Desktop-first, graceful degradation

| Device | Priority | Strategy |
|--------|----------|----------|
| Desktop | Primary | Full feature set, keyboard/mouse optimized |
| Tablet | Secondary | Functional, touch-friendly |
| Mobile | Tertiary | View-only, basic navigation |

### Breakpoint Strategy

Using Tailwind CSS default breakpoints:

| Breakpoint | Width | Layout |
|------------|-------|--------|
| Base | <640px | Single column, hamburger nav |
| `sm` | 640px+ | 2-column grid |
| `md` | 768px+ | 3-column grid |
| `lg` | 1024px+ | 4-column grid, full nav |
| `xl` | 1280px+ | Max-width container |

**Component Adaptations:**

| Component | Desktop | Tablet | Mobile |
|-----------|---------|--------|--------|
| Dashboard cards | 4 columns | 2 columns | 1 column |
| Navigation | Horizontal top | Horizontal top | Hamburger |
| Tables | Full columns | Horizontal scroll | Card layout |
| Drop zone | Full width | Full width | Full width |

### Accessibility Strategy

**Target:** WCAG 2.1 Level AA

| Requirement | Implementation |
|-------------|----------------|
| Color contrast | 4.5:1 minimum (met with palette) |
| Focus indicators | Visible ring on all interactive elements |
| Keyboard navigation | Full support, logical tab order |
| Screen readers | Semantic HTML, ARIA labels |
| Touch targets | Minimum 44×44px |
| Text sizing | Minimum 14px, respects user preferences |

**Color Independence:**
- Category colors always paired with text labels
- Trend indicators use icons + text, not color alone
- Error/warning states include icon indicators

**Interactive Elements:**
- Drop zone has keyboard-accessible file dialog fallback
- All dropdowns keyboard navigable
- Charts provide data table alternative

### Testing Strategy

| Type | Method |
|------|--------|
| Responsive | Browser DevTools, real device spot-checks |
| Accessibility | Lighthouse audits, keyboard-only navigation |
| Cross-browser | Chrome (primary), Firefox, Safari |

### Implementation Guidelines

**Responsive:**
- Use Tailwind responsive prefixes (`sm:`, `md:`, `lg:`)
- Test touch targets on actual devices
- Ensure readable text without zoom on mobile

**Accessibility:**
- Use semantic HTML (`<nav>`, `<main>`, `<button>`)
- Add `aria-label` to icon-only buttons
- Manage focus for modals and dropdowns
- Include skip-to-content link
