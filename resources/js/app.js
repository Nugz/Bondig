import './bootstrap';
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

Alpine.plugin(collapse);

/**
 * Bonus matching component for manual bonus-to-product matching.
 * Used in receipts/match-bonuses.blade.php
 *
 * Requires: <meta name="csrf-token" content="{{ csrf_token() }}"> in layout head
 */
Alpine.data('bonusMatching', () => ({
    matchedBonuses: [],
    receiptId: null,
    totalBonuses: 0,
    redirectUrl: '',
    redirectDelayMs: 1000, // Delay before redirect to show success state

    init() {
        // These values are set via x-init in the template
    },

    getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (!meta) {
            console.error('CSRF token meta tag not found. Ensure layout includes: <meta name="csrf-token" content="{{ csrf_token() }}">');
            return null;
        }
        return meta.content;
    },

    async handleMatch(bonusId, value) {
        if (!value) return;

        const csrfToken = this.getCsrfToken();
        if (!csrfToken) {
            alert('Security token missing. Please refresh the page and try again.');
            return;
        }

        const isNotApplicable = value === 'not_applicable';
        const data = isNotApplicable
            ? { not_applicable: true }
            : { line_item_id: value };

        try {
            const response = await fetch(`/receipts/${this.receiptId}/match-bonus/${bonusId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(data),
            });

            const result = await response.json();

            if (result.success) {
                this.matchedBonuses.push(bonusId);

                // Check if all bonuses are matched
                if (this.matchedBonuses.length === this.totalBonuses) {
                    setTimeout(() => {
                        window.location.href = this.redirectUrl;
                    }, this.redirectDelayMs);
                }
            } else {
                alert(result.error || 'Failed to match bonus');
            }
        } catch (error) {
            console.error('Error matching bonus:', error);
            alert('An error occurred. Please try again.');
        }
    }
}));

window.Alpine = Alpine;
Alpine.start();
