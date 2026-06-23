const STORAGE_KEY = 'stz_currency';
const DURATIONS = window.PricingConfig?.durations ?? {
    1: { months: 1, label: '1 Month', discount: 0 },
    3: { months: 3, label: '3 Months', discount: 0.07 },
    6: { months: 6, label: '6 Months', discount: 0.10 },
    12: { months: 12, label: '12 Months', discount: 0.20 },
};
const PAYMENT_METHODS = window.PricingConfig?.paymentMethods ?? {
    inr: [],
    usd: [],
};

let state = {
    currency: 'inr',
    duration: 1,
    region: 'IN',
};

function formatMoney(amount, currency) {
    if (currency === 'inr') {
        return `₹${Math.round(amount).toLocaleString('en-IN')}`;
    }
    const num = Number(amount);
    const formatted = num % 1 === 0 ? num : num.toFixed(2);
    return `$${formatted}`;
}

function calcPrice(monthly, months, discount) {
    const subtotal = monthly * months;
    const decimals = monthly < 100 ? 2 : 0;
    const factor = Math.pow(10, decimals);
    const total = Math.round(subtotal * (1 - discount) * factor) / factor;
    const saved = Math.round((subtotal - total) * factor) / factor;
    const perMonth = months > 1 ? Math.round((total / months) * factor) / factor : total;
    return { subtotal, total, saved, perMonth, discountPercent: Math.round(discount * 100) };
}

function updatePlanCards() {
    const duration = DURATIONS[state.duration];
    const months = duration.months;
    const discount = duration.discount;

    document.querySelectorAll('[data-price-inr]').forEach((card) => {
        const monthly = state.currency === 'inr'
            ? parseFloat(card.dataset.priceInr)
            : parseFloat(card.dataset.priceUsd);
        const calc = calcPrice(monthly, months, discount);

        const amountEl = card.querySelector('.plan-price-amount');
        const periodEl = card.querySelector('.plan-price-period');
        const subEl = card.querySelector('.plan-price-sub');
        const saveEl = card.querySelector('.plan-price-save');

        if (amountEl) {
            if (months === 1) {
                amountEl.textContent = formatMoney(calc.perMonth, state.currency);
            } else {
                amountEl.textContent = formatMoney(calc.total, state.currency);
            }
        }

        if (periodEl) {
            periodEl.textContent = months === 1 ? '/month' : `for ${months} months`;
        }

        if (subEl) {
            if (months === 1) {
                subEl.textContent = '';
                subEl.classList.add('hidden');
            } else {
                subEl.textContent = `${formatMoney(calc.perMonth, state.currency)}/mo · Save ${calc.discountPercent}%`;
                subEl.classList.remove('hidden');
            }
        }

        if (saveEl) {
            if (calc.saved > 0) {
                saveEl.textContent = `Save ${formatMoney(calc.saved, state.currency)}`;
                saveEl.classList.remove('hidden');
            } else {
                saveEl.classList.add('hidden');
            }
        }
    });

    updatePricingTables();
}

function updatePricingTables() {
    const duration = DURATIONS[state.duration];
    const months = duration.months;
    const discount = duration.discount;

    document.querySelectorAll('[data-table-price-inr]').forEach((row) => {
        const monthly = state.currency === 'inr'
            ? parseFloat(row.dataset.tablePriceInr)
            : parseFloat(row.dataset.tablePriceUsd);
        const calc = calcPrice(monthly, months, discount);
        const cell = row.querySelector('.table-price-cell');
        if (!cell) return;

        if (months === 1) {
            cell.textContent = `${formatMoney(calc.perMonth, state.currency)}/mo`;
        } else {
            cell.textContent = `${formatMoney(calc.total, state.currency)} (${calc.discountPercent}% off)`;
        }
    });
}

function updateCurrencyUI() {
    document.querySelectorAll('[data-currency-btn]').forEach((btn) => {
        const active = btn.dataset.currencyBtn === state.currency;
        btn.classList.toggle('bg-accent', active);
        btn.classList.toggle('text-white', active);
        btn.classList.toggle('text-ink-secondary', !active);
    });

    document.querySelectorAll('[data-show-inr]').forEach((el) => {
        el.classList.toggle('hidden', state.currency !== 'inr');
    });
    document.querySelectorAll('[data-show-usd]').forEach((el) => {
        el.classList.toggle('hidden', state.currency !== 'usd');
    });

    updatePaymentMethods();
    updatePlanCards();
}

function updatePaymentMethods() {
    const methods = PAYMENT_METHODS[state.currency] ?? [];
    document.querySelectorAll('[data-payment-methods]').forEach((container) => {
        container.innerHTML = methods.map((m) => `
            <span class="inline-flex items-center gap-1.5 rounded-full border border-line bg-white px-3 py-1.5 text-xs font-medium text-ink-secondary">
                <span class="h-1.5 w-1.5 rounded-full bg-success"></span>
                ${m.name}
            </span>
        `).join('');
    });

    const note = document.querySelector('[data-payment-note]');
    if (note) {
        note.textContent = state.currency === 'inr'
            ? 'India: Pay via UPI, PayPal, or Offline payment'
            : 'International: Pay via PayPal or Offline payment (UPI not available)';
    }
}

function setCurrency(currency) {
    if (state.region !== 'IN' && currency === 'inr') return;

    state.currency = currency;
    localStorage.setItem(STORAGE_KEY, currency);
    updateCurrencyUI();
}

function setDuration(months) {
    state.duration = months;
    document.querySelectorAll('[data-duration-btn]').forEach((btn) => {
        const active = parseInt(btn.dataset.durationBtn, 10) === months;
        btn.classList.toggle('bg-ink', active);
        btn.classList.toggle('text-white', active);
        btn.classList.toggle('text-ink-secondary', !active);
    });
    updatePlanCards();
}

function applyRegionRules() {
    const isIndia = state.region === 'IN';

    document.querySelectorAll('[data-currency-switcher]').forEach((wrapper) => {
        const inrBtn = wrapper.querySelector('[data-currency-btn="inr"]');
        const usdBtn = wrapper.querySelector('[data-currency-btn="usd"]');
        const lockedNote = wrapper.querySelector('[data-currency-locked]');

        if (!isIndia) {
            state.currency = 'usd';
            if (inrBtn) inrBtn.classList.add('hidden');
            if (usdBtn) {
                usdBtn.classList.add('bg-accent', 'text-white');
                usdBtn.classList.remove('text-ink-secondary');
            }
            if (lockedNote) lockedNote.classList.remove('hidden');
        } else {
            if (inrBtn) inrBtn.classList.remove('hidden');
            if (lockedNote) lockedNote.classList.add('hidden');
        }
    });
}

async function detectRegion() {
    const saved = localStorage.getItem(STORAGE_KEY);

    try {
        const res = await fetch('https://ipapi.co/json/', { signal: AbortSignal.timeout(4000) });
        const data = await res.json();
        state.region = data.country_code ?? 'IN';
    } catch {
        const tz = Intl.DateTimeFormat().resolvedOptions().timeZone ?? '';
        state.region = (tz.includes('Kolkata') || tz.includes('Calcutta')) ? 'IN' : 'US';
    }

    if (state.region === 'IN') {
        if (saved === 'inr' || saved === 'usd') {
            state.currency = saved;
        } else {
            state.currency = 'inr';
        }
    } else {
        state.currency = 'usd';
        localStorage.removeItem(STORAGE_KEY);
    }
}

function bindEvents() {
    document.querySelectorAll('[data-currency-btn]').forEach((btn) => {
        btn.addEventListener('click', () => setCurrency(btn.dataset.currencyBtn));
    });

    document.querySelectorAll('[data-duration-btn]').forEach((btn) => {
        btn.addEventListener('click', () => setDuration(parseInt(btn.dataset.durationBtn, 10)));
    });
}

export function getPricingState() {
    return { ...state };
}

export async function initPricing() {
    if (!document.querySelector('[data-pricing-root]')) return;

    bindEvents();
    await detectRegion();
    applyRegionRules();
    setDuration(1);
    updateCurrencyUI();
}
