export function initDashboard() {
    initMobileSidebar();
    initUpiPolling();
}

function initMobileSidebar() {
    const sidebar = document.getElementById('mobile-sidebar');
    const panel = document.getElementById('mobile-sidebar-panel');
    const backdrop = document.getElementById('mobile-sidebar-backdrop');
    const openBtn = document.getElementById('mobile-sidebar-open');
    const closeBtn = document.getElementById('mobile-sidebar-close');
    const headerOpenBtn = document.getElementById('mobile-header-menu');

    if (!sidebar || !panel) return;

    function openSidebar() {
        sidebar.classList.remove('pointer-events-none');
        sidebar.setAttribute('aria-hidden', 'false');
        backdrop?.classList.remove('opacity-0');
        panel.classList.remove('-translate-x-full');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        sidebar.classList.add('pointer-events-none');
        sidebar.setAttribute('aria-hidden', 'true');
        backdrop?.classList.add('opacity-0');
        panel.classList.add('-translate-x-full');
        document.body.style.overflow = '';
    }

    openBtn?.addEventListener('click', openSidebar);
    headerOpenBtn?.addEventListener('click', openSidebar);
    closeBtn?.addEventListener('click', closeSidebar);
    backdrop?.addEventListener('click', closeSidebar);

    document.querySelectorAll('[data-close-mobile-sidebar]').forEach((el) => {
        el.addEventListener('click', closeSidebar);
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeSidebar();
    });
}

function initUpiPolling() {
    const pollUrl = window.UPI_POLL_URL;
    if (!pollUrl) return;

    const statusEl = document.getElementById('upi-status');
    const expiryEl = document.querySelector('[data-upi-expiry]');
    const countdownEl = document.querySelector('[data-countdown]');

    if (expiryEl && countdownEl) {
        const expiresAt = parseInt(expiryEl.dataset.expires, 10) * 1000;
        const tick = () => {
            const remaining = Math.max(0, expiresAt - Date.now());
            const mins = Math.floor(remaining / 60000);
            const secs = Math.floor((remaining % 60000) / 1000);
            countdownEl.textContent = `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
        };
        tick();
        setInterval(tick, 1000);
    }

    const poll = async () => {
        try {
            const res = await fetch(pollUrl, { headers: { Accept: 'application/json' } });
            const data = await res.json();
            if (statusEl) statusEl.textContent = data.label;
            if (data.completed) {
                window.location.reload();
            } else if (data.expired) {
                window.location.reload();
            }
        } catch {
            /* retry on next interval */
        }
    };

    poll();
    setInterval(poll, 10000);
}
