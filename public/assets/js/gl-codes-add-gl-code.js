// public/assets/js/gl-codes-add-gl-code.js
// Handles modal open/close for GL Codes Management page (Add GL Code)

document.addEventListener('DOMContentLoaded', function () {
    // Modal open button
    const openBtn = document.querySelector("button[onclick^='openModal']");
    // Modal element
    const modal = document.getElementById('modal-add-gl-code');
    // Modal backdrop
    const backdrop = modal ? modal.querySelector('.modal-backdrop') : null;
    // Modal panel
    const panel = modal ? modal.querySelector('.modal-panel') : null;

    // Open modal function
    function openModal() {
        if (!modal) return;
        modal.classList.remove('hidden');
        setTimeout(() => {
            if (backdrop) backdrop.classList.remove('opacity-0');
            if (panel) panel.classList.remove('opacity-0', 'scale-95');
        }, 10);
    }

    // Close modal function
    function closeModal() {
        if (!modal) return;
        if (backdrop) backdrop.classList.add('opacity-0');
        if (panel) panel.classList.add('opacity-0', 'scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 200);
    }

    // Attach open event
    if (openBtn) {
        openBtn.addEventListener('click', openModal);
    }

    // Attach close events
    if (backdrop) {
        backdrop.addEventListener('click', closeModal);
    }
    const closeBtns = modal ? modal.querySelectorAll("button[onclick^='closeModal']") : [];
    closeBtns.forEach(btn => btn.addEventListener('click', closeModal));

    // AJAX form submission for Add GL Code
    const form = document.getElementById('form-add-gl-code');
    const saveBtn = document.getElementById('btn-save-gl-code');
    if (form && saveBtn) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving...';
            const formData = new FormData(form);
            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(async (res) => {
                const contentType = res.headers.get('content-type') || '';
                const rawText = await res.text();

                if (!contentType.includes('application/json')) {
                    throw new Error('Unexpected response format from server.');
                }

                try {
                    return JSON.parse(rawText);
                } catch (parseErr) {
                    // Fallback: some environments prepend warnings before JSON.
                    const jsonStart = rawText.indexOf('{');
                    const jsonEnd = rawText.lastIndexOf('}');
                    if (jsonStart !== -1 && jsonEnd > jsonStart) {
                        try {
                            return JSON.parse(rawText.slice(jsonStart, jsonEnd + 1));
                        } catch (fallbackErr) {
                            // Keep the original error path below.
                        }
                    }
                    console.error('Invalid JSON raw response:', rawText);
                    throw new Error('Invalid JSON response from server.');
                }
            })
            .then(data => {
                console.log('GL Code Add Response:', data); // Debugging output
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save Code';
                showGlCodeAlert(data.success, data.message || data.error);
                if (data.success) {
                    form.reset();
                    closeModal();
                    // Optionally: reload table or fetch new data here
                }
            })
            .catch((err) => {
                console.error('GL Code Add Error:', err); // Debugging output
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save Code';
                showGlCodeAlert(false, 'An error occurred. Please try again.');
            });
        });
    }

    // Show custom alert
    function showGlCodeAlert(success, message) {
        let alertDiv = document.getElementById('gl-code-alert');
        if (!alertDiv) {
            alertDiv = document.createElement('div');
            alertDiv.id = 'gl-code-alert';
            alertDiv.className = 'fixed top-6 left-1/2 -translate-x-1/2 z-[200] px-6 py-3 rounded-xl shadow-lg text-sm font-bold';
            document.body.appendChild(alertDiv);
        }
        alertDiv.textContent = message;
        alertDiv.style.display = 'block';
        alertDiv.classList.remove('bg-green-50', 'bg-red-50', 'text-green-800', 'text-red-800', 'border-green-200', 'border-red-200');
        if (success) {
            alertDiv.classList.add('bg-green-50', 'text-green-800', 'border', 'border-green-200');
        } else {
            alertDiv.classList.add('bg-red-50', 'text-red-800', 'border', 'border-red-200');
        }
        setTimeout(() => {
            alertDiv.style.display = 'none';
        }, 3500);
    }

    window.openModal = openModal;
    window.closeModal = closeModal;
});
