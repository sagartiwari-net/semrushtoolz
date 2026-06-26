const COOKIE_NAME = 'semrush_device_fp';

async function sha256Hex(value) {
    const data = new TextEncoder().encode(value);
    const hash = await crypto.subtle.digest('SHA-256', data);

    return Array.from(new Uint8Array(hash))
        .map((byte) => byte.toString(16).padStart(2, '0'))
        .join('');
}

export async function computeDeviceFingerprint() {
    const parts = [
        navigator.userAgent || '',
        navigator.language || '',
        navigator.platform || '',
        `${screen.width}x${screen.height}`,
        String(screen.colorDepth || ''),
        Intl.DateTimeFormat().resolvedOptions().timeZone || '',
        String(navigator.hardwareConcurrency || ''),
        String(navigator.maxTouchPoints || 0),
    ];

    return sha256Hex(parts.join('|'));
}

function setFingerprintCookie(fingerprint) {
    const secure = window.location.protocol === 'https:' ? ';Secure' : '';

    document.cookie = `${COOKIE_NAME}=${fingerprint};path=/;max-age=31536000;SameSite=Lax${secure}`;
}

function ensureFormField(form, fingerprint) {
    let input = form.querySelector('input[name="device_fingerprint"]');

    if (!input) {
        input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'device_fingerprint';
        form.appendChild(input);
    }

    input.value = fingerprint;
}

export async function initDeviceSecurity() {
    if (!window.crypto?.subtle) {
        return null;
    }

    const fingerprint = await computeDeviceFingerprint();

    setFingerprintCookie(fingerprint);

    document.querySelectorAll('form').forEach((form) => {
        ensureFormField(form, fingerprint);
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (form instanceof HTMLFormElement) {
            ensureFormField(form, fingerprint);
        }
    }, true);

    return fingerprint;
}
