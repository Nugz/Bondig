---
stepsCompleted: [1, 2, 3, 4]
status: complete
inputDocuments:
  - "_bmad-output/planning-artifacts/prd.md"
  - "_bmad-output/planning-artifacts/architecture.md"
  - "_bmad-output/planning-artifacts/ux-design-specification.md"
---

# Bondig - Epic Breakdown

## Overview

This document provides the complete epic and story breakdown for Bondig, decomposing the requirements from the PRD, UX Design if it exists, and Architecture requirements into implementable stories.

## Requirements Inventory

### Functional Requirements

**Receipt Ingestion (FR1-FR4)**
```
FR1: User can upload a single AH PDF receipt via drag-and-drop or file picker
FR2: User can upload multiple AH PDF receipts in a single batch operation
FR3: User can manually enter a receipt for non-AH stores (store name, date, line items)
FR4: System can detect and reject duplicate receipts (same store + date + total)
```

**Data Parsing & Extraction (FR5-FR11)**
```
FR5: System can extract product names from AH PDF receipts
FR6: System can extract product quantities from AH PDF receipts
FR7: System can extract product prices (unit and total) from AH PDF receipts
FR8: System can extract receipt date and time from AH PDF receipts
FR9: System can extract receipt total amount from AH PDF receipts
FR10: System can identify bonus/discount items from AH PDF receipts
FR11: User can view the original parsed text for any line item (debug mode)
```

**Product Categorization (FR12-FR17)**
```
FR12: System can auto-categorize products using AI
FR13: System can display confidence level for each AI categorization
FR14: User can override any product category assignment
FR15: System can learn from user corrections to improve future categorizations
FR16: User can view items needing category review (low-confidence items)
FR17: User can confirm AI-suggested categories with single action
```

**Product Management (FR18-FR21)**
```
FR18: User can view all unique products across all receipts
FR19: User can merge duplicate/variant products into a single product
FR20: User can edit product details (name, category) for any product
FR21: User can view purchase history for any product
```

**Spending Analytics (FR22-FR27)**
```
FR22: User can view total spending for any selected time period
FR23: User can view spending breakdown by category
FR24: User can drill down from category to individual products
FR25: User can compare spending between two time periods
FR26: User can view spending trends over time (visual)
FR27: User can filter analytics by store
```

**Price Tracking (FR28-FR30)**
```
FR28: System can track price changes for the same product over time
FR29: User can view price history for any product
FR30: User can identify products with price increases
```

**Budget Management (FR31-FR34)**
```
FR31: User can set a monthly budget target
FR32: User can view current month spending vs budget
FR33: User can view remaining budget with days left in month
FR34: User can view budget history (actual vs target by month)
```

**Data Correction (FR35-FR38)**
```
FR35: User can edit any line item on a parsed receipt (price, quantity, name)
FR36: User can delete incorrectly parsed line items
FR37: User can add missing line items to a parsed receipt
FR38: User can delete an entire receipt
```

**System Administration (FR39-FR40)**
```
FR39: User can define and manage product categories
FR40: User can view parsing/import history with status
```

### NonFunctional Requirements

**Performance**
```
NFR1: Page load time < 2 seconds for all views
NFR2: PDF parsing completes in < 5 seconds per receipt
NFR3: AI categorization completes in < 10 seconds per batch
NFR4: Dashboard queries execute in < 1 second
```

**Data Integrity**
```
NFR5: No silent data loss - all parsing errors must surface visibly to user
NFR6: Database must be easily exportable/backupable (SQLite file copy)
NFR7: Original parsed text retained for debugging (audit trail)
NFR8: Receipt imports are atomic - all-or-nothing (transactional safety)
```

**External Dependencies**
```
NFR9: Graceful degradation when Gemini API unavailable - queue for retry
NFR10: Respect Gemini free tier rate limits - batch requests to conserve quota
NFR11: On API failure - user notified, manual categorization fallback available
```

**Security**
```
NFR12: Local/private hosting - no public exposure required
NFR13: No authentication required - single-user personal tool
NFR14: Data encryption not required - personal spending data on private host
```

### Additional Requirements

**From Architecture - Starter Template & Infrastructure:**
- Project initialization using Bare Laravel 12 with Tailwind CSS
- Initialization commands: `composer create-project laravel/laravel bondig`, then install Tailwind, spatie/pdf-to-text, google-gemini-php/laravel
- SQLite database for simplicity (single file, easy backup)
- Local machine hosting with `php artisan serve`
- Vite for asset bundling

**From Architecture - Technical Implementation:**
- Service layer architecture: ReceiptParsingService, CategorizationService, ProductMatchingService, AnalyticsService
- Product matching via normalized names (lowercase, trimmed) with manual merge UI for edge cases
- API key storage in .env file
- Flash messages + database logging for error handling
- Alpine.js for frontend interactivity
- Chart.js for data visualization

**From Architecture - Database Schema:**
- 6 core tables: categories, products, receipts, line_items, budgets, import_logs
- Products table includes: name, normalized_name, category_id, confidence, user_confirmed
- Line items reference both receipt and product
- Import logs track parsing history and errors

**From Architecture - Implementation Patterns:**
- Laravel naming conventions (snake_case DB, PascalCase PHP)
- Controllers handle HTTP only, business logic in Services
- FormRequest classes for complex validation
- Carbon for date handling with Europe/Amsterdam timezone

**From UX Design - Design System:**
- Tailwind CSS + daisyUI component library
- Inter font family with system fallback
- Teal primary color (#0D9488), Stone background (#FAFAF9)
- Category colors: Produce (green), Dairy (blue), Meat (red), Bakery (amber), Beverages (cyan), Snacks (purple), Household (slate), Other (stone)

**From UX Design - Layout & Components:**
- Card grid dashboard layout (4-column responsive)
- Full page upload with large drag-drop zone
- Table layout for category detail/product lists
- Top navigation bar
- Custom components: Upload Drop Zone, Category Card, Parse Result Summary, Price Sparkline, Category Review List

**From UX Design - UX Patterns:**
- Drag-and-drop upload with instant visual feedback
- One-click category override with inline editing
- Progressive disclosure: overview first, drill-down on demand
- Batch confirmation workflows for category review
- Toast notifications for confirmations (auto-dismiss 4s)

**From UX Design - Accessibility:**
- WCAG 2.1 Level AA target
- 4.5:1 color contrast minimum
- Keyboard navigation support
- 44x44px minimum touch targets
- Focus indicators on all interactive elements

### FR Coverage Map

| FR | Epic | Description |
|----|------|-------------|
| FR1 | Epic 1 | Single PDF upload |
| FR2 | Epic 1 | Batch PDF upload |
| FR3 | Epic 6 | Manual receipt entry |
| FR4 | Epic 1 | Duplicate detection |
| FR5 | Epic 1 | Extract product names |
| FR6 | Epic 1 | Extract quantities |
| FR7 | Epic 1 | Extract prices |
| FR8 | Epic 1 | Extract date/time |
| FR9 | Epic 1 | Extract total |
| FR10 | Epic 1 | Identify bonus items |
| FR11 | Epic 1 | View original parsed text |
| FR12 | Epic 2 | AI auto-categorize |
| FR13 | Epic 2 | Display confidence |
| FR14 | Epic 2 | Override category |
| FR15 | Epic 2 | Learn from corrections |
| FR16 | Epic 2 | View items needing review |
| FR17 | Epic 2 | Confirm with single action |
| FR18 | Epic 6 | View all products |
| FR19 | Epic 6 | Merge duplicate products |
| FR20 | Epic 6 | Edit product details |
| FR21 | Epic 6 | View purchase history |
| FR22 | Epic 3 | View spending by time period |
| FR23 | Epic 3 | Category breakdown |
| FR24 | Epic 3 | Drill-down to products |
| FR25 | Epic 3 | Compare periods |
| FR26 | Epic 3 | View trends visually |
| FR27 | Epic 3 | Filter by store |
| FR28 | Epic 4 | Track price changes |
| FR29 | Epic 4 | View price history |
| FR30 | Epic 4 | Identify price increases |
| FR31 | Epic 5 | Set monthly target |
| FR32 | Epic 5 | View spending vs budget |
| FR33 | Epic 5 | View remaining budget |
| FR34 | Epic 5 | View budget history |
| FR35 | Epic 6 | Edit line items |
| FR36 | Epic 6 | Delete line items |
| FR37 | Epic 6 | Add line items |
| FR38 | Epic 6 | Delete receipts |
| FR39 | Epic 2 | Manage categories |
| FR40 | Epic 6 | View import history |

## Epic List

### Epic 1: Receipt Upload & Parsing

**Goal:** Enable users to upload AH PDF receipts and see all purchases parsed correctly into structured data.

**User Outcome:** "I can upload my Albert Heijn PDF receipts and see every product, price, and quantity extracted automatically."

**FRs covered:** FR1, FR2, FR4, FR5, FR6, FR7, FR8, FR9, FR10, FR11

**Key Deliverables:**
- Project initialization (Laravel 12 + Tailwind + daisyUI + dependencies)
- Database schema and migrations
- Upload page with drag-drop zone
- ReceiptParsingService for PDF text extraction
- ProductMatchingService for normalized product lookup
- Receipt and line item display views
- Import logging for audit trail

---

### Epic 2: AI Categorization & Category Management

**Goal:** Enable automatic product categorization with AI, with user review and correction capabilities that improve the system over time.

**User Outcome:** "My products are automatically categorized, I can easily review and fix any mistakes, and the system gets smarter from my corrections."

**FRs covered:** FR12, FR13, FR14, FR15, FR16, FR17, FR39

**Key Deliverables:**
- CategorizationService with Gemini API integration
- Category model and seeder with default taxonomy
- Confidence display on products
- Category review page (low-confidence items)
- Inline category editing with "apply to all" option
- Category CRUD for managing taxonomy
- Learning mechanism (user_confirmed flag)

---

### Epic 3: Spending Dashboard & Analytics

**Goal:** Provide a comprehensive dashboard showing spending patterns with category breakdowns, drill-down capability, and period comparisons.

**User Outcome:** "I can see exactly where my grocery money goes, compare months, and discover spending patterns I never noticed."

**FRs covered:** FR22, FR23, FR24, FR25, FR26, FR27

**Key Deliverables:**
- AnalyticsService for spending calculations
- Dashboard with category cards (grid layout)
- Time range selector (month picker)
- Category detail view with product breakdown
- Period comparison (this month vs last month)
- Chart.js visualizations (doughnut, line charts)
- Store filter for multi-store support

---

### Epic 4: Price Tracking

**Goal:** Track price changes on products over time to identify inflation and make informed purchasing decisions.

**User Outcome:** "I can see how prices have changed on products I buy regularly and catch price increases early."

**FRs covered:** FR28, FR29, FR30

**Key Deliverables:**
- Price history tracking (via line_items over time)
- Product detail page with price history
- Price sparkline component
- Price increase detection and highlighting
- Trend indicators on product lists

---

### Epic 5: Budget Management

**Goal:** Enable setting and tracking monthly grocery budgets with clear progress visualization.

**User Outcome:** "I can set a monthly grocery budget and always know where I stand - how much I've spent and how much is left."

**FRs covered:** FR31, FR32, FR33, FR34

**Key Deliverables:**
- Budget model and CRUD
- Budget setting form
- Progress bar on dashboard
- Remaining budget with days context
- Budget history view (actual vs target by month)

---

### Epic 6: Product Management & Data Correction

**Goal:** Provide complete control over the product catalog and ability to fix any data errors, including manual entry for non-AH purchases.

**User Outcome:** "I can manage my product list, merge duplicates, fix any parsing errors, and add purchases from other stores."

**FRs covered:** FR3, FR18, FR19, FR20, FR21, FR35, FR36, FR37, FR38, FR40

**Key Deliverables:**
- Product list page with search/filter
- Product merge interface
- Product edit form
- Purchase history per product
- Manual receipt entry form
- Line item editing (inline)
- Receipt deletion with confirmation
- Import history/logs view

---

## Epic 1: Receipt Upload & Parsing

**Goal:** Enable users to upload AH PDF receipts and see all purchases parsed correctly into structured data.

**User Outcome:** "I can upload my Albert Heijn PDF receipts and see every product, price, and quantity extracted automatically."

### Story 1.1: Project Foundation & App Shell

As a **user**,
I want **the Bondig application set up and accessible**,
So that **I can navigate the app and access its features**.

**Acceptance Criteria:**

**Given** I have the project dependencies installed
**When** I run `php artisan serve`
**Then** the application loads at localhost:8000
**And** I see a navigation bar with links to Dashboard, Upload, Products
**And** the page uses the Bondig color scheme (teal primary, stone background)
**And** daisyUI components render correctly

**Technical Notes:**
- Initialize Laravel 12 with `composer create-project laravel/laravel bondig`
- Install Tailwind CSS, daisyUI, Alpine.js
- Install spatie/pdf-to-text, google-gemini-php/laravel
- Create app layout with top navigation
- Configure SQLite database
- Set timezone to Europe/Amsterdam

---

### Story 1.2: Single Receipt Upload & Parsing

As a **user**,
I want **to upload an Albert Heijn PDF receipt and see all products parsed automatically**,
So that **my purchase data is captured in the system without manual entry**.

**Acceptance Criteria:**

**Given** I am on the Upload page
**When** I drag and drop an AH PDF receipt onto the drop zone
**Then** the file is accepted and processing begins
**And** I see a progress indicator during parsing
**And** the system extracts all product names, quantities, and prices
**And** the system extracts the receipt date and time
**And** the system extracts the receipt total amount
**And** the system identifies bonus/discount items
**And** I see a success summary with item count and total amount
**And** the receipt is saved to the database

**Given** I upload a non-PDF file
**When** the system processes the file
**Then** I see an error message "Only PDF files are accepted"

**Given** I upload a PDF that cannot be parsed
**When** the system fails to extract data
**Then** I see an error message with details
**And** the error is logged to import_logs table

**Technical Notes:**
- Create migrations: receipts, line_items, products, import_logs tables
- Create Receipt, LineItem, Product, ImportLog models
- Create ReceiptParsingService for PDF text extraction
- Create ProductMatchingService for normalized product lookup
- Create Upload page with drag-drop zone component
- Store original raw_text for debugging

---

### Story 1.3: Receipt List & Detail Views

As a **user**,
I want **to view my uploaded receipts and see the parsed line items**,
So that **I can verify the data was captured correctly and debug any issues**.

**Acceptance Criteria:**

**Given** I have uploaded receipts
**When** I navigate to the Receipts list
**Then** I see all receipts sorted by date (newest first)
**And** each receipt shows: date, store, total amount, item count

**Given** I am viewing the receipt list
**When** I click on a receipt
**Then** I see the receipt detail page
**And** I see all line items with product name, quantity, unit price, total price
**And** bonus items are visually indicated
**And** the receipt total is displayed

**Given** I am on a receipt detail page
**When** I click "View original text" on a line item
**Then** I see the original parsed text from the PDF
**And** I can close the debug view

**Technical Notes:**
- Create ReceiptController with index and show methods
- Create receipts/index.blade.php with receipt cards
- Create receipts/show.blade.php with line item table
- Add raw_text display toggle using Alpine.js

---

### Story 1.4: Batch Receipt Upload

As a **user**,
I want **to upload multiple PDF receipts at once**,
So that **I can quickly import my historical receipt data**.

**Acceptance Criteria:**

**Given** I am on the Upload page
**When** I drag and drop multiple PDF files onto the drop zone
**Then** all files are queued for processing
**And** I see a progress indicator showing "Processing X of Y receipts"
**And** each receipt is parsed individually
**And** I see a summary showing successful and failed imports

**Given** I upload 10 receipts and 2 fail to parse
**When** processing completes
**Then** I see "8 receipts imported successfully, 2 failed"
**And** I can view details of the failures
**And** the 8 successful receipts are saved

**Given** I select files using the file picker (click to browse)
**When** I select multiple files
**Then** they are processed the same as drag-and-drop

**Technical Notes:**
- Extend upload component to handle multiple files
- Process files sequentially to manage memory
- Show per-file status (success/error)
- Aggregate results in summary card

---

### Story 1.5: Duplicate Receipt Detection

As a **user**,
I want **the system to detect and reject duplicate receipts**,
So that **I don't accidentally import the same receipt twice**.

**Acceptance Criteria:**

**Given** I have already uploaded a receipt from AH on 2026-01-15 with total €127.43
**When** I upload another PDF with the same store, date, and total
**Then** the system detects it as a duplicate
**And** I see a warning "Duplicate receipt detected - already imported"
**And** the duplicate is not saved

**Given** I upload a batch with some duplicates
**When** processing completes
**Then** duplicates are flagged but non-duplicates are imported
**And** the summary shows "3 imported, 2 duplicates skipped"

**Given** I have a receipt with same date but different total
**When** I upload it
**Then** it is accepted as a new receipt (not a duplicate)

**Technical Notes:**
- Add unique constraint check: store + purchased_at + total_amount
- Check for duplicates before saving
- Return appropriate feedback for duplicates
- Handle duplicates gracefully in batch uploads

---

## Epic 2: AI Categorization & Category Management

**Goal:** Enable automatic product categorization with AI, with user review and correction capabilities that improve the system over time.

**User Outcome:** "My products are automatically categorized, I can easily review and fix any mistakes, and the system gets smarter from my corrections."

### Story 2.1: Category Model & Default Taxonomy

As a **user**,
I want **a set of default grocery categories available in the system**,
So that **products can be organized into meaningful spending groups**.

**Acceptance Criteria:**

**Given** the application is freshly installed
**When** I run database migrations and seeders
**Then** the categories table is created
**And** default categories are seeded: Dairy, Meat & Fish, Produce, Bread & Bakery, Beverages, Snacks, Frozen, Household, Personal Care, Other
**And** each category has an assigned color for visual distinction

**Given** I view any product list
**When** categories are displayed
**Then** I see colored category badges using daisyUI badge component
**And** the colors match the defined category colors

**Technical Notes:**
- Create categories migration with: id, name, color, timestamps
- Create Category model with Product relationship
- Create CategorySeeder with default taxonomy and colors
- Create category-badge Blade component

---

### Story 2.2: Category Management CRUD

As a **user**,
I want **to add, edit, and delete product categories**,
So that **I can customize the taxonomy to match my shopping habits**.

**Acceptance Criteria:**

**Given** I navigate to Categories management
**When** the page loads
**Then** I see all categories with their colors and product counts

**Given** I am on the Categories page
**When** I click "Add Category"
**Then** I see a form to enter name and select color
**And** I can save the new category

**Given** I have a category "Snacks"
**When** I click edit and change the name to "Snacks & Treats"
**Then** the category is updated
**And** all products in that category reflect the new name

**Given** I have a category with 0 products
**When** I click delete
**Then** the category is removed

**Given** I have a category with products assigned
**When** I try to delete it
**Then** I see a warning "This category has X products. Reassign them first."
**And** the deletion is blocked

**Technical Notes:**
- Create CategoryController with full CRUD
- Create categories/index.blade.php with category list
- Create category form (create/edit) with color picker
- Prevent deletion of categories with products

---

### Story 2.3: AI Auto-Categorization

As a **user**,
I want **new products automatically categorized by AI when I upload receipts**,
So that **I don't have to manually categorize every product**.

**Acceptance Criteria:**

**Given** I upload a receipt with new products
**When** the receipt is parsed
**Then** each new product is sent to Gemini API for categorization
**And** the AI assigns a category from the available taxonomy
**And** a confidence score (0-1) is stored with each assignment

**Given** a product already exists in the database with a category
**When** it appears on a new receipt
**Then** the AI is NOT called (uses cached category)
**And** API calls are conserved

**Given** the Gemini API is unavailable
**When** I upload a receipt
**Then** products are saved without categories
**And** I see a warning "AI categorization unavailable - manual review needed"
**And** the receipt import still succeeds

**Given** the Gemini API rate limit is reached
**When** I upload receipts
**Then** uncategorized products are queued for later processing
**And** I'm informed about the delay

**Technical Notes:**
- Create CategorizationService with Gemini API integration
- Store API key in .env, access via config('services.gemini.key')
- Add confidence and user_confirmed columns to products table
- Implement caching: only call API for new normalized_names
- Handle API errors gracefully with fallback

---

### Story 2.4: Confidence Display & Review Queue

As a **user**,
I want **to see which products need category review and how confident the AI was**,
So that **I can focus my attention on fixing incorrect categorizations**.

**Acceptance Criteria:**

**Given** I view any product list
**When** products have AI-assigned categories
**Then** I see a confidence indicator (high/medium/low) next to the category
**And** low confidence (<70%) is visually highlighted

**Given** I navigate to "Review Categories"
**When** the page loads
**Then** I see only products with low confidence OR no category
**And** products are sorted by confidence (lowest first)
**And** I see the count of items needing review

**Given** I have 15 products needing review
**When** I view the review page
**Then** I can see all 15 in a scannable list
**And** each shows: product name, current category (if any), confidence level

**Given** I am on the Dashboard
**When** there are items needing review
**Then** I see a badge/notification "X items need review"

**Technical Notes:**
- Add confidence threshold constant (0.7 = 70%)
- Create categories/review.blade.php with filtered product list
- Add confidence indicator component (icon or badge)
- Add review count to navigation/dashboard

---

### Story 2.5: Category Override & Learning

As a **user**,
I want **to easily change product categories and have the system learn from my corrections**,
So that **future categorizations improve over time**.

**Acceptance Criteria:**

**Given** I see a product with wrong category
**When** I click the category badge
**Then** a dropdown appears with all categories
**And** I can select the correct category with one click
**And** the change saves immediately (no save button)

**Given** I correct a product's category
**When** the save completes
**Then** the product is marked as user_confirmed = true
**And** future AI suggestions will respect this override

**Given** I correct "AH Chips Paprika" from "Household" to "Snacks"
**When** I see the confirmation
**Then** I'm asked "Apply to all similar products?" with count
**And** if I confirm, all products matching "Chips" pattern update

**Given** I am on the Review page with multiple items
**When** I select several items
**Then** I can bulk-assign a category to all selected
**And** I can "Confirm all" AI suggestions with one click

**Technical Notes:**
- Create inline category dropdown with Alpine.js
- Update product via AJAX, show toast confirmation
- Implement "apply to all" with pattern matching on normalized_name
- Add bulk actions to review page
- Set user_confirmed = true on manual assignment

---

## Epic 3: Spending Dashboard & Analytics

**Goal:** Provide a comprehensive dashboard showing spending patterns with category breakdowns, drill-down capability, and period comparisons.

**User Outcome:** "I can see exactly where my grocery money goes, compare months, and discover spending patterns I never noticed."

### Story 3.1: Dashboard Layout & Month Overview

As a **user**,
I want **a dashboard showing my total spending for the selected month**,
So that **I can quickly see how much I've spent on groceries**.

**Acceptance Criteria:**

**Given** I navigate to the Dashboard
**When** the page loads
**Then** I see the current month selected by default
**And** I see my total spending for that month prominently displayed
**And** I see the number of receipts and items for the period

**Given** I am on the Dashboard
**When** I click the month selector
**Then** I can choose any month with data
**And** the dashboard updates instantly without page reload

**Given** I select a month with no data
**When** the dashboard updates
**Then** I see "No spending data for this month"
**And** I'm prompted to upload receipts

**Technical Notes:**
- Create DashboardController with index method
- Create AnalyticsService for spending calculations
- Create dashboard/index.blade.php with card layout
- Implement month picker with Alpine.js
- Calculate totals from line_items joined with receipts

---

### Story 3.2: Category Breakdown Cards

As a **user**,
I want **to see my spending broken down by category**,
So that **I can understand where my grocery money goes**.

**Acceptance Criteria:**

**Given** I am on the Dashboard with spending data
**When** the page loads
**Then** I see a grid of category cards (4 columns on desktop)
**And** each card shows: category name, total amount, percentage of total
**And** cards are sorted by spending (highest first)
**And** each card has the category's color accent

**Given** I have spending in 8 categories
**When** I view the dashboard
**Then** all 8 categories are displayed
**And** percentages add up to 100%

**Given** a category has €0 spending
**When** I view the dashboard
**Then** that category is not displayed (or shown last with €0)

**Technical Notes:**
- Extend AnalyticsService with getCategoryBreakdown()
- Create category-card Blade component
- Use daisyUI card with colored left border
- Calculate percentages in service layer

---

### Story 3.3: Category Drill-Down to Products

As a **user**,
I want **to click a category and see which products I bought**,
So that **I can understand exactly what's driving my spending**.

**Acceptance Criteria:**

**Given** I am on the Dashboard
**When** I click a category card (e.g., "Snacks - €94")
**Then** I navigate to the category detail page
**And** I see all products in that category for the selected period
**And** products show: name, quantity bought, total spent
**And** products are sorted by spending (highest first)

**Given** I am on the category detail page
**When** I view the product list
**Then** I see a back link to the Dashboard
**And** the selected month is preserved
**And** I see the category total at the top

**Given** I bought "AH Chips" 6 times for €18 total
**When** I view Snacks category
**Then** I see "AH Chips - 6x - €18.00"

**Technical Notes:**
- Create CategoryDetailController or extend DashboardController
- Create dashboard/category.blade.php with product table
- Query line_items grouped by product for the category
- Maintain month selection via query parameter

---

### Story 3.4: Period Comparison

As a **user**,
I want **to compare my spending between two months**,
So that **I can see if I'm spending more or less than before**.

**Acceptance Criteria:**

**Given** I am on the Dashboard viewing January
**When** I enable comparison mode
**Then** I can select a comparison month (defaults to previous month)
**And** each category card shows the delta (e.g., "+12%" or "-€15")
**And** increases are shown in red/amber, decreases in green

**Given** I'm comparing January vs December
**When** Dairy was €62 in December and €89 in January
**Then** I see "Dairy €89 (+44%)" or "Dairy €89 (+€27)"
**And** the increase is visually highlighted

**Given** I'm comparing months
**When** the total spending differs
**Then** I see the overall change at the top (e.g., "€487 vs €435 last month (+12%)")

**Given** a category exists in current month but not comparison month
**When** I view the comparison
**Then** I see "New" indicator instead of percentage

**Technical Notes:**
- Add comparison_month query parameter
- Extend AnalyticsService with getComparisonData()
- Update category cards to show delta when comparing
- Use color coding for positive/negative changes

---

### Story 3.5: Spending Trends Visualization

As a **user**,
I want **to see visual charts of my spending over time**,
So that **I can identify patterns and trends at a glance**.

**Acceptance Criteria:**

**Given** I am on the Dashboard
**When** I view the charts section
**Then** I see a doughnut chart showing category distribution
**And** I see a line chart showing monthly spending trend (last 6 months)

**Given** I hover over a chart segment
**When** the tooltip appears
**Then** I see the category name and amount

**Given** I have 6 months of data
**When** I view the trend chart
**Then** I see spending per month as a line graph
**And** I can identify increases/decreases visually

**Given** I click on a month in the trend chart
**When** the click registers
**Then** the dashboard updates to show that month

**Technical Notes:**
- Install Chart.js via npm
- Create chart-container Blade component
- Create doughnut chart for category breakdown
- Create line chart for monthly trends
- Use Alpine.js for chart interactions

---

### Story 3.6: Store Filter

As a **user**,
I want **to filter my analytics by store**,
So that **I can see spending for just Albert Heijn or other stores separately**.

**Acceptance Criteria:**

**Given** I am on the Dashboard
**When** I see the filter controls
**Then** I see a store dropdown with "All Stores" selected by default
**And** I see all stores I've shopped at listed

**Given** I have receipts from "Albert Heijn" and "Lidl"
**When** I select "Albert Heijn" from the filter
**Then** all analytics update to show only AH spending
**And** category cards reflect AH-only data
**And** charts update to show AH-only data

**Given** I have a store filter active
**When** I navigate to category drill-down
**Then** the store filter is preserved
**And** I only see products from that store

**Technical Notes:**
- Add store filter dropdown to dashboard header
- Pass store parameter through all analytics queries
- Update AnalyticsService methods to accept optional store filter
- Persist filter in URL query parameter

---

## Epic 4: Price Tracking

**Goal:** Track price changes on products over time to identify inflation and make informed purchasing decisions.

**User Outcome:** "I can see how prices have changed on products I buy regularly and catch price increases early."

### Story 4.1: Price History Tracking

As a **user**,
I want **the system to track price changes for products over time**,
So that **I can see how prices have changed on items I buy regularly**.

**Acceptance Criteria:**

**Given** I buy "BEEMSTER 48+" on January 5th for €5.49
**When** I buy it again on February 10th for €5.99
**Then** the system records both prices with their dates
**And** I can see the price history for this product

**Given** a product has multiple purchases at different prices
**When** the price data is queried
**Then** prices are returned chronologically
**And** each price includes the purchase date

**Given** I buy the same product twice on the same receipt
**When** the receipt is processed
**Then** only one price point is recorded for that date
**And** the unit price (not total) is tracked

**Technical Notes:**
- Price history is derived from line_items table (unit_price + receipt.purchased_at)
- No separate price_history table needed - query line_items grouped by product
- Create method in Product model: getPriceHistory()
- Calculate price change percentage between first and last purchase

---

### Story 4.2: Product Price History View

As a **user**,
I want **to view the price history for any product**,
So that **I can see how much prices have changed over time**.

**Acceptance Criteria:**

**Given** I navigate to a product detail page
**When** the page loads
**Then** I see the current price (most recent purchase)
**And** I see a price history chart (sparkline or line chart)
**And** I see price change summary (e.g., "+€0.50 (+9%) over 3 months")

**Given** a product has 6 price points over time
**When** I view the price history
**Then** I see all 6 points plotted on a chart
**And** I can hover to see date and price for each point

**Given** a product has only 1 purchase
**When** I view the product
**Then** I see "Not enough data for price trend"
**And** the sparkline is not displayed

**Given** I am on any product list
**When** products have price history
**Then** I see a mini sparkline next to each product
**And** the sparkline shows last 6 price points

**Technical Notes:**
- Create products/show.blade.php with price history section
- Create price-sparkline Blade component using Chart.js
- Query line_items for product ordered by date
- Display sparklines inline on product lists (48x16px)

---

### Story 4.3: Price Increase Detection

As a **user**,
I want **to easily identify products that have increased in price**,
So that **I can catch inflation early and consider alternatives**.

**Acceptance Criteria:**

**Given** "BEEMSTER 48+" went from €5.49 to €6.29 (>10% increase)
**When** I view any product list containing this item
**Then** I see a visual indicator (red up arrow or badge)
**And** the increase amount/percentage is shown

**Given** I am on the Dashboard
**When** there are products with significant price increases (>10%)
**Then** I see a "Price Alerts" section or card
**And** it shows top 5 products with biggest increases

**Given** I want to see all price increases
**When** I click "View all price changes"
**Then** I see a dedicated page listing products by price change
**And** products are sorted by increase percentage (highest first)
**And** I can filter to show only increases or all changes

**Given** a product decreased in price
**When** I view the price change
**Then** I see a green indicator showing the decrease
**And** decreases are shown as positive news

**Technical Notes:**
- Add getPriceChange() method to Product model
- Compare oldest and newest price in last 3 months
- Create price-change-indicator component
- Add "Price Alerts" widget to dashboard
- Create dedicated price-changes page with filtering

---

## Epic 5: Budget Management

**Goal:** Enable setting and tracking monthly grocery budgets with clear progress visualization.

**User Outcome:** "I can set a monthly grocery budget and always know where I stand - how much I've spent and how much is left."

### Story 5.1: Budget Model & Setting

As a **user**,
I want **to set a monthly grocery budget target**,
So that **I have a spending goal to work towards**.

**Acceptance Criteria:**

**Given** I navigate to Budget settings
**When** the page loads
**Then** I see a form to set my monthly budget
**And** I see the current budget if one exists

**Given** I enter €450 as my monthly budget
**When** I click Save
**Then** the budget is saved for the current month
**And** I see a confirmation message

**Given** I have no budget set for a month
**When** I view that month's dashboard
**Then** I see "No budget set" with a prompt to set one
**And** I can click to set a budget

**Given** I want to change my budget mid-month
**When** I edit the budget amount
**Then** the new amount is saved
**And** progress calculations update immediately

**Technical Notes:**
- Create budgets migration with: id, month (date), target_amount, timestamps
- Create Budget model
- Create BudgetController with store/update methods
- Create budget setting form (simple input + save)
- Month stored as first day of month for easy querying

---

### Story 5.2: Budget Progress Display

As a **user**,
I want **to see my current spending compared to my budget**,
So that **I know if I'm on track for the month**.

**Acceptance Criteria:**

**Given** I have a €450 budget and spent €275
**When** I view the Dashboard
**Then** I see a progress bar showing 61% used
**And** I see "€275 of €450 spent"
**And** the progress bar is colored appropriately (green/amber/red)

**Given** I've spent less than 75% of budget
**When** I view the progress bar
**Then** it displays in green (on track)

**Given** I've spent 75-90% of budget
**When** I view the progress bar
**Then** it displays in amber (caution)

**Given** I've spent more than 90% of budget
**When** I view the progress bar
**Then** it displays in red (warning)

**Given** I've exceeded my budget (>100%)
**When** I view the Dashboard
**Then** I see "€520 of €450 spent (€70 over budget)"
**And** the progress bar shows overflow state

**Technical Notes:**
- Add budget progress component to dashboard header
- Query current month's budget and spending total
- Use daisyUI progress component with dynamic colors
- Calculate percentage: (spent / target) * 100

---

### Story 5.3: Remaining Budget with Context

As a **user**,
I want **to see my remaining budget with days left in the month**,
So that **I can pace my spending appropriately**.

**Acceptance Criteria:**

**Given** I have €175 remaining with 8 days left in the month
**When** I view the budget widget
**Then** I see "€175 remaining"
**And** I see "8 days left in January"
**And** I see daily budget suggestion "~€22/day"

**Given** I have €50 remaining with 15 days left
**When** I view the budget widget
**Then** I see the daily budget is tight "~€3/day"
**And** visual indicator shows this is challenging

**Given** it's the last day of the month
**When** I view the budget widget
**Then** I see "Last day of month"
**And** remaining amount is clearly displayed

**Given** I have no budget set
**When** I view the dashboard
**Then** the remaining/days context is not shown
**And** I'm prompted to set a budget

**Technical Notes:**
- Calculate days remaining: end of month - today
- Calculate daily budget: remaining / days_left
- Display in budget widget alongside progress bar
- Use Carbon for date calculations

---

### Story 5.4: Budget History View

As a **user**,
I want **to see my budget performance over past months**,
So that **I can track my progress over time and set realistic targets**.

**Acceptance Criteria:**

**Given** I have 6 months of budget history
**When** I navigate to Budget History
**Then** I see a table/list showing each month
**And** each row shows: month, target, actual, difference
**And** months are sorted newest first

**Given** I was under budget in March (€420 of €450)
**When** I view that month's row
**Then** I see "€420 / €450" with green indicator
**And** I see "-€30 (under budget)"

**Given** I was over budget in April (€510 of €450)
**When** I view that month's row
**Then** I see "€510 / €450" with red indicator
**And** I see "+€60 (over budget)"

**Given** I view the budget history
**When** I look at the summary
**Then** I see average monthly spending
**And** I see how many months I hit my target
**And** I see a trend chart of budget vs actual

**Technical Notes:**
- Create budget/index.blade.php with history table
- Query all budgets with actual spending joined
- Calculate actual from sum of line_items per month
- Add summary statistics at top
- Optional: Chart.js bar chart comparing target vs actual

---

## Epic 6: Product Management & Data Correction

**Goal:** Provide complete control over the product catalog and ability to fix any data errors, including manual entry for non-AH purchases.

**User Outcome:** "I can manage my product list, merge duplicates, fix any parsing errors, and add purchases from other stores."

### Story 6.1: Product List & Search

As a **user**,
I want **to view all products in my system and search/filter them**,
So that **I can find specific products and manage my product catalog**.

**Acceptance Criteria:**

**Given** I navigate to the Products page
**When** the page loads
**Then** I see all unique products in a paginated list
**And** each product shows: name, category, purchase count, last purchased date

**Given** I have 200 products
**When** I view the product list
**Then** I see pagination (20 per page)
**And** I can navigate between pages

**Given** I want to find "Beemster"
**When** I type in the search box
**Then** the list filters to show matching products
**And** search works on product name

**Given** I want to see only Dairy products
**When** I select "Dairy" from the category filter
**Then** only products in that category are shown

**Technical Notes:**
- Create ProductController with index method
- Create products/index.blade.php with table/list view
- Implement search with query parameter
- Add category filter dropdown
- Paginate results (20 per page)

---

### Story 6.2: Product Edit

As a **user**,
I want **to edit product details like name and category**,
So that **I can fix incorrect data and organize products properly**.

**Acceptance Criteria:**

**Given** I am viewing a product
**When** I click "Edit"
**Then** I see a form with current name and category
**And** I can modify both fields

**Given** I change a product name from "AH BOEMSTER" to "AH BEEMSTER"
**When** I save the changes
**Then** the product name is updated
**And** I see a confirmation message
**And** all historical line items still reference this product

**Given** I change a product's category
**When** I save the changes
**Then** the category is updated
**And** the product is marked as user_confirmed = true

**Technical Notes:**
- Add edit/update methods to ProductController
- Create products/edit.blade.php with form
- Use UpdateProductRequest for validation
- Redirect back to product list with success message

---

### Story 6.3: Product Merge

As a **user**,
I want **to merge duplicate or variant products into one**,
So that **my analytics accurately reflect the same product over time**.

**Acceptance Criteria:**

**Given** I have "BEEMSTER 48+" and "BEEMSTER 48+ KAAS" as separate products
**When** I select merge from one product's page
**Then** I see a search to find the product to merge into
**And** I can select the target product

**Given** I'm merging "BEEMSTER 48+ KAAS" into "BEEMSTER 48+"
**When** I confirm the merge
**Then** all line items from source move to target product
**And** the source product is deleted
**And** I see "Merged X line items into BEEMSTER 48+"

**Given** I'm about to merge products
**When** I see the confirmation screen
**Then** I see both product names clearly
**And** I see the count of line items that will be moved
**And** I must confirm to proceed

**Given** I accidentally started a merge
**When** I want to cancel
**Then** I can click Cancel and nothing changes

**Technical Notes:**
- Create products/merge.blade.php with search/select UI
- Add merge method to ProductController
- Update all line_items.product_id to target
- Delete source product after merge
- Log merge action for audit trail

---

### Story 6.4: Product Purchase History

As a **user**,
I want **to see when and where I purchased a product**,
So that **I can understand my buying patterns for specific items**.

**Acceptance Criteria:**

**Given** I navigate to a product detail page
**When** the page loads
**Then** I see a list of all purchases of this product
**And** each entry shows: date, store, quantity, price, receipt link

**Given** I bought "AH Milk" 15 times over 3 months
**When** I view its purchase history
**Then** I see all 15 purchases in chronological order (newest first)
**And** I can click any entry to go to that receipt

**Given** I view purchase history
**When** I look at the summary
**Then** I see total quantity purchased
**And** I see total amount spent on this product
**And** I see average price paid

**Technical Notes:**
- Add purchase history section to products/show.blade.php
- Query line_items with receipt data for the product
- Include links to receipt detail pages
- Calculate summary statistics

---

### Story 6.5: Manual Receipt Entry

As a **user**,
I want **to manually enter receipts from non-AH stores**,
So that **I can track all my grocery spending in one place**.

**Acceptance Criteria:**

**Given** I navigate to "Add Manual Receipt"
**When** the form loads
**Then** I see fields for: store name, date, and line items
**And** I can add multiple line items

**Given** I'm entering a Lidl receipt
**When** I fill in store "Lidl", date, and add 3 items
**Then** each item has: product name, quantity, price, category
**And** I can add/remove line items dynamically

**Given** I submit a manual receipt
**When** the data is valid
**Then** the receipt is saved with all line items
**And** new products are created (or matched to existing)
**And** I see the receipt detail page

**Given** I enter a product that already exists
**When** I type a similar name
**Then** I see suggestions from existing products
**And** I can select an existing product or create new

**Technical Notes:**
- Create receipts/create.blade.php with dynamic form
- Use Alpine.js for add/remove line items
- Create StoreReceiptRequest for validation
- Match products by normalized_name or create new
- Set pdf_path to null for manual entries

---

### Story 6.6: Line Item Editing

As a **user**,
I want **to edit, delete, or add line items on a receipt**,
So that **I can fix parsing errors and ensure data accuracy**.

**Acceptance Criteria:**

**Given** I'm viewing a receipt with a parsing error (€34.56 should be €3.45)
**When** I click edit on that line item
**Then** I can modify the price, quantity, or product name
**And** changes save immediately

**Given** a line item was incorrectly parsed and is garbage
**When** I click delete on that line item
**Then** I see a confirmation prompt
**And** the line item is removed from the receipt
**And** the receipt total is recalculated

**Given** a product was missed during parsing
**When** I click "Add line item" on the receipt
**Then** I can enter product name, quantity, price
**And** the new item is added to the receipt
**And** the receipt total is recalculated

**Given** I edit a line item
**When** I save the changes
**Then** I see a toast confirmation
**And** the receipt view updates immediately

**Technical Notes:**
- Create LineItemController with update/destroy/store methods
- Add inline editing UI with Alpine.js
- Recalculate receipt total_amount after changes
- Log changes to import_logs for audit

---

### Story 6.7: Receipt Deletion

As a **user**,
I want **to delete an entire receipt**,
So that **I can remove incorrectly imported or duplicate data**.

**Acceptance Criteria:**

**Given** I'm viewing a receipt I want to delete
**When** I click "Delete Receipt"
**Then** I see a confirmation modal
**And** the modal shows receipt date, store, total, and item count
**And** warns "This will delete X line items"

**Given** I confirm deletion
**When** the deletion processes
**Then** the receipt and all its line items are deleted
**And** I'm redirected to the receipt list
**And** I see "Receipt deleted successfully"

**Given** I accidentally clicked delete
**When** I see the confirmation modal
**Then** I can click Cancel to abort
**And** nothing is deleted

**Technical Notes:**
- Add destroy method to ReceiptController
- Cascade delete line_items with receipt
- Use modal for confirmation (Alpine.js)
- Redirect to receipts.index after deletion

---

### Story 6.8: Import History View

As a **user**,
I want **to view my import history and any parsing errors**,
So that **I can track what was imported and troubleshoot issues**.

**Acceptance Criteria:**

**Given** I navigate to Import History
**When** the page loads
**Then** I see a list of all imports (newest first)
**And** each entry shows: date, filename, status, item count, error count

**Given** an import had errors
**When** I view that import entry
**Then** I see status "Partial" or "Failed"
**And** I can click to see error details

**Given** I click on an import with errors
**When** the details expand
**Then** I see each error with: line number, original text, error message
**And** I can click to go to the receipt (if created)

**Given** I have many imports
**When** I view the history
**Then** I can filter by status (all, success, partial, failed)
**And** the list is paginated

**Technical Notes:**
- Create ImportLogController with index/show methods
- Create admin/import-logs.blade.php
- Query import_logs with receipt relationship
- Display errors from JSON column
- Add status filter and pagination
