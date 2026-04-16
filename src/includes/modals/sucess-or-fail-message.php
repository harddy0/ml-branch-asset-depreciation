<template id="result-modal-template">
    <div id="result-modal-overlay" class="result-modal-overlay" aria-live="polite">
        <div class="result-modal-card" role="status">
            <div class="result-modal-icon" data-result-icon="success">
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M20 6L9 17l-5-5"/>
                </svg>
            </div>
            <div class="result-modal-icon hidden" data-result-icon="fail">
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </div>
            <div class="result-modal-content">
                <div class="result-modal-message" data-result-message></div>
                <div class="result-modal-actions">
                    <button type="button" class="result-modal-ok" data-result-ok>OK</button>
                </div>
            </div>
        </div>
    </div>
</template>