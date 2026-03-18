// ============================================================
//  asset-import.js — Asset Import page scripts
//  Depends on: main.js
// ============================================================

document.addEventListener('DOMContentLoaded', function () {
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('file-upload');
    const fileDisplay = document.getElementById('file-display');
    const fileNameTxt = document.getElementById('file-name');
    const btnCancel = document.getElementById('btn-cancel');
    
    if (!dropZone || !fileInput) return;

    // Click to open file dialog
    dropZone.addEventListener('click', function (e) {
        // Prevent clicking if the overlay is active (unless clicking cancel)
        if (e.target.closest('#file-display') && !e.target.closest('#btn-cancel')) return;
        fileInput.click();
    });

    // Handle Drag Events for styling
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(function (eventName) {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(function (eventName) {
        dropZone.addEventListener(eventName, function () {
            dropZone.classList.add('border-red-500', 'bg-red-50');
            dropZone.classList.remove('border-slate-300');
        }, false);
    });

    ['dragleave', 'drop'].forEach(function (eventName) {
        dropZone.addEventListener(eventName, function () {
            dropZone.classList.remove('border-red-500', 'bg-red-50');
            dropZone.classList.add('border-slate-300');
        }, false);
    });

    // Handle Drop
    dropZone.addEventListener('drop', function (e) {
        let dt = e.dataTransfer;
        let files = dt.files;
        handleFiles(files);
    });

    // Handle File Input Change
    fileInput.addEventListener('change', function () {
        handleFiles(this.files);
    });

    function handleFiles(files) {
        if (files.length > 0) {
            const file = files[0];
            fileNameTxt.textContent = file.name;
            fileDisplay.classList.remove('hidden');
            fileDisplay.classList.add('flex');
            
            // Note: UI only for now. Ready for backend submission.
            console.log("File ready for processing:", file.name);
        }
    }

    // Cancel selection
    btnCancel.addEventListener('click', function (e) {
        e.stopPropagation(); // prevent re-triggering the file dialog
        fileInput.value = ''; // clear input
        fileDisplay.classList.add('hidden');
        fileDisplay.classList.remove('flex');
    });
});