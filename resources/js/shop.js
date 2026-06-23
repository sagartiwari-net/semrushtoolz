import { getPricingState } from './pricing';

function buildCheckoutUrl(type, slug) {
    const { currency, duration } = getPricingState();
    const params = new URLSearchParams({
        [type]: slug,
        duration_months: String(duration),
        currency,
    });
    return `/dashboard/checkout?${params.toString()}`;
}

function bindCheckoutLinks() {
    document.querySelectorAll('[data-checkout-plan]').forEach((link) => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            window.location.href = buildCheckoutUrl('plan', link.dataset.checkoutPlan);
        });
    });

    document.querySelectorAll('[data-checkout-tool]').forEach((link) => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            window.location.href = buildCheckoutUrl('tool', link.dataset.checkoutTool);
        });
    });
}

function updateComboHeroPrice() {
    const priceEl = document.querySelector('.combo-hero-price');
    if (!priceEl) return;

    const { currency, duration } = getPricingState();
    const monthlyInr = parseInt(priceEl.dataset.monthlyInr || '799', 10);
    const monthlyUsd = parseInt(priceEl.dataset.monthlyUsd || '12', 10);
    const monthly = currency === 'inr' ? monthlyInr : monthlyUsd;
    const discount = { 1: 0, 3: 0.07, 6: 0.1, 12: 0.2 }[duration] ?? 0;
    const months = duration;
    const total = currency === 'usd'
        ? Math.round(monthly * months * (1 - discount) * 100) / 100
        : Math.round(monthly * months * (1 - discount));

    if (currency === 'inr') {
        priceEl.innerHTML = months === 1
            ? `₹${monthly}<small class="text-sm font-normal text-white/60">/month</small>`
            : `₹${total}<small class="text-sm font-normal text-white/60"> for ${months} months</small>`;
    } else {
        priceEl.innerHTML = months === 1
            ? `$${monthly}<small class="text-sm font-normal text-white/60">/month</small>`
            : `$${total}<small class="text-sm font-normal text-white/60"> for ${months} months</small>`;
    }
}

export function initShop() {
    const hasCheckout = document.querySelector('[data-checkout-plan], [data-checkout-tool]');
    if (!hasCheckout) return;

    bindCheckoutLinks();

    document.querySelectorAll('[data-duration-btn], [data-currency-btn]').forEach((btn) => {
        btn.addEventListener('click', () => {
            setTimeout(updateComboHeroPrice, 50);
        });
    });

    updateComboHeroPrice();
}
