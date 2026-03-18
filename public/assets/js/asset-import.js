// ============================================================
//  asset-import.js — Asset Import page scripts
//  Depends on: main.js
// ============================================================

document.addEventListener('DOMContentLoaded', function () {
    const dropZone    = document.getElementById('drop-zone');
    const fileInput   = document.getElementById('file-upload');
    const fileDisplay = document.getElementById('file-display');
    const fileNameTxt = document.getElementById('file-name');
    const btnCancel   = document.getElementById('btn-cancel');
    const form        = document.getElementById('import-form');
    
    // Safety check in case the script loads on a page without these elements
    if (!dropZone || !fileInput) return;

    // ── 1. Click to Browse ──────────────────────────────────────────
    dropZone.addEventListener('click', function (e) {
        // Prevent opening the file dialog if the user is clicking the overlay buttons
        if (e.target.closest('#file-display') && !e.target.closest('#btn-cancel')) {
            return;
        }
        fileInput.click();
    });

    // ── 2. Drag and Drop Visual Feedback ────────────────────────────
    // Prevent default browser behavior (opening the file in the tab)
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(function (eventName) {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    // Highlight drop zone when file is dragged over it
    ['dragenter', 'dragover'].forEach(function (eventName) {
        dropZone.addEventListener(eventName, function () {
            dropZone.classList.add('border-red-500', 'bg-red-50');
            dropZone.classList.remove('border-slate-300');
        }, false);
    });

    // Remove highlight when file leaves or is dropped
    ['dragleave', 'drop'].forEach(function (eventName) {
        dropZone.addEventListener(eventName, function () {
            dropZone.classList.remove('border-red-500', 'bg-red-50');
            dropZone.classList.add('border-slate-300');
        }, false);
    });

    // ── 3. Handle File Selection (Drop & Click) ─────────────────────
    // Handle dropped files
    dropZone.addEventListener('drop', function (e) {
        let dt = e.dataTransfer;
        
        // Assign the dropped files to the hidden input so the form can submit them
        fileInput.files = dt.files; 
        
        handleFiles(fileInput.files);
    });

    // Handle files selected via the native browser dialog
    fileInput.addEventListener('change', function () {
        handleFiles(this.files);
    });

    // Process the selected file for the UI
    function handleFiles(files) {
        if (files.length > 0) {
            const file = files[0];
            
            // Optional: You could add basic client-side validation here for file extensions
            const validExtensions = ['csv', 'xlsx', 'xls'];
            const fileExt = file.name.split('.').pop().toLowerCase();
            
            if (!validExtensions.includes(fileExt)) {
                alert('Invalid file type. Please upload a .csv or .xlsx file.');
                fileInput.value = ''; // Reset
                return;
            }

            // Update UI
            fileNameTxt.textContent = file.name;
            fileDisplay.classList.remove('hidden');
            fileDisplay.classList.add('flex');
        }
    }

    // ── 4. Cancel Selection ─────────────────────────────────────────
    btnCancel.addEventListener('click', function (e) {
        e.stopPropagation();  // Stop the click from bubbling up to the dropZone
        fileInput.value = ''; // Clear the actual file input
        
        // Hide the overlay
        fileDisplay.classList.add('hidden');
        fileDisplay.classList.remove('flex');
    });
});