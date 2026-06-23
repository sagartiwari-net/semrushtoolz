import { initPricing } from './pricing';
import { initTrialPricing } from './trial-pricing';
import { initDashboard } from './dashboard';
import { initShop } from './shop';
import { initWhatsAppFloat } from './whatsapp';

document.addEventListener('DOMContentLoaded', () => {
    initPricing();
    initTrialPricing();
    initDashboard();
    initShop();
    initWhatsAppFloat();
});
