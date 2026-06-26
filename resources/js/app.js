import { initPricing } from './pricing';
import { initTrialPricing } from './trial-pricing';
import { initDashboard } from './dashboard';
import { initShop } from './shop';
import { initWhatsAppFloat } from './whatsapp';
import { initDeviceSecurity } from './device-security';

document.addEventListener('DOMContentLoaded', () => {
    initDeviceSecurity();
    initPricing();
    initTrialPricing();
    initDashboard();
    initShop();
    initWhatsAppFloat();
});
