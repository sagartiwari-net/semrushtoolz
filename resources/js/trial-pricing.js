import { getPricingState } from './pricing';

function formatMoney(amount, currency) {
    if (currency === 'inr') {
        return `₹${Math.round(amount).toLocaleString('en-IN')}`;
    }
    const num = Number(amount);
    const formatted = num % 1 === 0 ? num : num.toFixed(1);
    return `$${formatted}`;
}

function updateTrialCard(root) {
    const { currency } = getPricingState();
    const activeBtn = root.querySelector('[data-trial-duration-btn].is-active')
        ?? root.querySelector('[data-trial-duration-btn]');

    if (!activeBtn) {
        return;
    }

    const days = activeBtn.dataset.trialDurationBtn;
    const inr = parseFloat(activeBtn.dataset.priceInr || '0');
    const usd = parseFloat(activeBtn.dataset.priceUsd || '0');
    const amount = currency === 'inr' ? inr : usd;
    const card = root.querySelector('.trial-plan-card');

    if (card) {
        card.dataset.priceInr = String(inr);
        card.dataset.priceUsd = String(usd);
    }

    const amountEl = root.querySelector('.trial-price-amount');
    if (amountEl) {
        amountEl.textContent = formatMoney(amount, currency);
    }

    const checkout = root.querySelector('[data-trial-checkout]');
    if (checkout && days) {
        const planId = root.dataset.planId;
        const params = new URLSearchParams({
            plan: planId,
            duration_days: days,
            currency,
        });
        checkout.href = `/subscribe?${params.toString()}`;
    }
}

export function initTrialPricing() {
    const root = document.querySelector('[data-trial-pricing-root]');
    if (!root) {
        return;
    }

    root.querySelectorAll('[data-trial-duration-btn]').forEach((btn) => {
        btn.addEventListener('click', () => {
            root.querySelectorAll('[data-trial-duration-btn]').forEach((other) => {
                other.classList.remove('is-active', 'bg-ink', 'text-white');
                other.classList.remove('is-active');
            });
            btn.classList.add('is-active');
            updateTrialCard(root);
        });
    });

    document.querySelectorAll('[data-currency-btn]').forEach((btn) => {
        btn.addEventListener('click', () => {
            setTimeout(() => updateTrialCard(root), 50);
        });
    });

    updateTrialCard(root);
}
