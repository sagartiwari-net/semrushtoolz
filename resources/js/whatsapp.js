export function initWhatsAppFloat() {
    document.querySelectorAll('[data-whatsapp-float]').forEach((el) => {
        el.addEventListener('click', (event) => {
            event.preventDefault();

            const phone = el.dataset.phone;
            if (!phone) {
                return;
            }

            const prefix = el.dataset.messagePrefix || 'Hi Semrushtoolz! I need help.';
            const pageUrl = window.location.href;
            const message = `${prefix}\n\nPage: ${pageUrl}`;
            const url = `https://wa.me/${phone}?text=${encodeURIComponent(message)}`;

            window.open(url, '_blank', 'noopener,noreferrer');
        });
    });
}
