import { initPricing } from './pricing';
import { initDashboard } from './dashboard';
import { initShop } from './shop';
import { initWhatsAppFloat } from './whatsapp';

document.addEventListener('DOMContentLoaded', () => {
    initPricing();
    initDashboard();
    initShop();
    initWhatsAppFloat();
});
