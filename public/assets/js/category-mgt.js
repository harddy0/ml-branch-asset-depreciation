document.addEventListener('DOMContentLoaded', function () {
    var categoriesTbody = document.getElementById('categories-tbody');
    var addBtn = document.getElementById('btn-add-category');
    var categoryModal = document.getElementById('category-modal');
    var categoryForm = document.getElementById('category-form');
    var categoryModalTitle = document.getElementById('category-modal-title');
    var depreciationCodeInput = document.getElementById('depreciation_code');
    var originalCodeInput = document.getElementById('original_depreciation_code');
    var descriptionInput = document.getElementById('description');
    var monthsInput = document.getElementById('input_months');
    var monthsToYearsDisplay = document.getElementById('months_to_years_display');
    var glCodeSelect = document.getElementById('gl_code_select');
    var glCodePickerInput = document.getElementById('gl_code_picker');
    var glCodeDropdown = document.getElementById('gl_code_dropdown');
    var codeLockedHint = document.getElementById('code-locked-hint');
    var deleteModal = document.getElementById('modal-delete-confirmation');
    var deleteForm = document.getElementById('delete-confirm-form');
    var deleteHiddenFields = document.getElementById('delete-hidden-fields');
    var deleteMessage = document.getElementById('delete-modal-message');

    if (!categoriesTbody || !categoryForm) {
        return;
    }

    var storeAction = categoryForm.getAttribute('action') || '';
    var updateAction = storeAction.replace('pl_rule_store.php', 'pl_rule_update.php');
    var deleteAction = storeAction.replace('pl_rule_store.php', 'pl_rule_delete.php');
    var categoriesApiUrl = '../api/get_categories.php';
    var glCodesApiUrl = '../api/get_gl_codes.php';
    var glCodesCache = [];

    function stripBom(text) {
        return (text || '').replace(/^\uFEFF/, '').trim();
    }

    function parseJsonResponse(rawText) {
        var cleaned = stripBom(rawText);
        if (!cleaned) {
            throw new Error('Empty server response.');
        }

        return JSON.parse(cleaned);
    }

    function escapeHtml(value) {
        var div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    }

    function renderEmptyRow(message) {
        categoriesTbody.innerHTML =
            '<tr>' +
            '<td colspan="5" class="px-4 py-8 text-center text-slate-500 text-sm">' +
            escapeHtml(message || 'No categories found.') +
            '</td>' +
            '</tr>';
    }

    function formatGlLabel(item) {
        var glCode = item.gl_code || '';
        var description = item.description || '';
        var accountType = (item.account_type || '').toUpperCase();
        var label = glCode;
        if (description) {
            label += ' - ' + description;
        }
        if (accountType) {
            label += ' (' + accountType + ')';
        }
        return label;
    }

    function updateMonthsToYearsDisplay() {
        if (!monthsToYearsDisplay) {
            return;
        }

        var months = monthsInput ? parseFloat(monthsInput.value) : 0;
        if (isNaN(months) || months <= 0) {
            monthsToYearsDisplay.textContent = 'Equivalent: 0.00 years';
            return;
        }

        var years = months / 12;
        monthsToYearsDisplay.textContent = 'Equivalent: ' + years.toFixed(2) + ' years';
    }

    function findGlByPickerText(value) {
        var normalized = (value || '').trim().toLowerCase();
        if (!normalized) {
            return null;
        }

        for (var i = 0; i < glCodesCache.length; i++) {
            var item = glCodesCache[i] || {};
            var code = (item.gl_code || '').toLowerCase();
            var label = formatGlLabel(item).toLowerCase();
            if (code === normalized || label === normalized) {
                return item;
            }
        }

        return null;
    }

    function setCodeEditableUi(isEditable) {
        if (!depreciationCodeInput) {
            return;
        }

        if (isEditable) {
            depreciationCodeInput.readOnly = false;
            depreciationCodeInput.classList.remove('bg-slate-100', 'text-slate-500', 'cursor-not-allowed', 'border-amber-300');
            depreciationCodeInput.classList.add('bg-slate-50', 'text-slate-700');
            if (codeLockedHint) {
                codeLockedHint.classList.add('hidden');
            }
        } else {
            depreciationCodeInput.readOnly = true;
            depreciationCodeInput.classList.remove('bg-slate-50', 'text-slate-700');
            depreciationCodeInput.classList.add('bg-slate-100', 'text-slate-500', 'cursor-not-allowed', 'border-amber-300');
            if (codeLockedHint) {
                codeLockedHint.classList.remove('hidden');
            }
        }
    }

    function closeGlDropdown() {
        if (!glCodeDropdown) {
            return;
        }

        glCodeDropdown.classList.add('hidden');
    }

    function openGlDropdown() {
        if (!glCodeDropdown) {
            return;
        }

        glCodeDropdown.classList.remove('hidden');
    }

    function clearGlSelection() {
        if (glCodeSelect) {
            glCodeSelect.value = '';
        }
    }

    function setGlSelection(item) {
        if (!item) {
            clearGlSelection();
            return;
        }

        if (glCodeSelect) {
            glCodeSelect.value = item.gl_code || '';
        }
        if (glCodePickerInput) {
            glCodePickerInput.value = formatGlLabel(item);
        }
    }

    function renderGlDropdown(items) {
        if (!glCodeDropdown) {
            return;
        }

        glCodeDropdown.innerHTML = '';

        if (!items.length) {
            var empty = document.createElement('div');
            empty.className = 'px-3 py-2 text-xs text-slate-500';
            empty.textContent = 'No matching GL account found.';
            glCodeDropdown.appendChild(empty);
            openGlDropdown();
            return;
        }

        for (var i = 0; i < items.length; i++) {
            (function () {
                var item = items[i] || {};
                var row = document.createElement('button');
                row.type = 'button';
                row.className = 'w-full text-left px-3 py-2 hover:bg-slate-50 border-b border-slate-100 last:border-b-0';

                var line1 = document.createElement('div');
                line1.className = 'text-xs font-bold text-slate-700';
                line1.textContent = (item.gl_code || '') + ' - ' + (item.description || '');

                var line2 = document.createElement('div');
                line2.className = 'text-[10px] font-semibold uppercase tracking-wide text-slate-500';
                line2.textContent = item.account_type || '';

                row.appendChild(line1);
                row.appendChild(line2);
                row.addEventListener('click', function () {
                    setGlSelection(item);
                    closeGlDropdown();
                });

                glCodeDropdown.appendChild(row);
            })();
        }

        openGlDropdown();
    }

    function syncHiddenGlCodeFromPicker() {
        if (!glCodePickerInput || !glCodeSelect) {
            return;
        }

        var selected = findGlByPickerText(glCodePickerInput.value);
        glCodeSelect.value = selected ? (selected.gl_code || '') : '';
    }

    function filterGlCodes(searchTerm) {
        var term = (searchTerm || '').toLowerCase();
        if (!term) {
            return glCodesCache.slice();
        }

        var filtered = [];
        for (var i = 0; i < glCodesCache.length; i++) {
            var item = glCodesCache[i] || {};
            var haystack = ((item.gl_code || '') + ' ' + (item.description || '') + ' ' + (item.account_type || '')).toLowerCase();
            if (haystack.indexOf(term) !== -1) {
                filtered.push(item);
            }
        }
        return filtered;
    }

    function renderCategories(rows) {
        if (!rows || !rows.length) {
            renderEmptyRow('No categories found.');
            return;
        }

        var html = '';
        for (var i = 0; i < rows.length; i++) {
            var row = rows[i] || {};
            var code = row.depreciation_code || '';
            var description = row.description || '';
            var months = parseInt(row.months, 10);
            if (isNaN(months)) {
                months = 0;
            }
            var glCode = row.gl_code || '';
            var glDescription = row.gl_description || '';
            var glDisplay = glCode;
            if (glCode && glDescription) {
                glDisplay = glCode + ' - ' + glDescription;
            }

            html +=
                '<tr class="border-b border-slate-100 hover:bg-slate-50">' +
                '<td class="py-3 px-4 font-semibold text-slate-800">' + escapeHtml(code) + '</td>' +
                '<td class="py-3 px-4">' + escapeHtml(description) + '</td>' +
                '<td class="py-3 px-4">' + escapeHtml(months) + '</td>' +
                '<td class="py-3 px-4">' + escapeHtml(glDisplay || '-') + '</td>' +
                '<td class="py-3 px-4 text-right">' +
                '<button type="button" class="btn-edit-category mr-2 px-3 py-1.5 text-xs font-bold uppercase tracking-wider rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-100" ' +
                'data-code="' + escapeHtml(code) + '" ' +
                'data-description="' + escapeHtml(description) + '" ' +
                'data-months="' + escapeHtml(months) + '" ' +
                'data-gl-code="' + escapeHtml(glCode) + '">Edit</button>' +
                '<button type="button" class="btn-delete-category px-3 py-1.5 text-xs font-bold uppercase tracking-wider rounded-lg border border-red-300 text-red-700 hover:bg-red-50" ' +
                'data-code="' + escapeHtml(code) + '" ' +
                'data-description="' + escapeHtml(description) + '">Delete</button>' +
                '</td>' +
                '</tr>';
        }

        categoriesTbody.innerHTML = html;
    }

    function setAddMode() {
        categoryForm.reset();
        categoryForm.setAttribute('action', storeAction);
        if (categoryModalTitle) {
            categoryModalTitle.textContent = 'Add Category';
        }
        if (originalCodeInput) {
            originalCodeInput.value = '';
        }
        setCodeEditableUi(true);
        if (monthsInput) {
            monthsInput.value = '';
        }
        updateMonthsToYearsDisplay();
        if (glCodePickerInput) {
            glCodePickerInput.value = '';
        }
        clearGlSelection();
        renderGlDropdown(glCodesCache);
        closeGlDropdown();
    }

    function setEditMode(data) {
        var months = parseInt(data.months, 10);
        if (isNaN(months)) {
            months = 0;
        }

        categoryForm.setAttribute('action', updateAction);
        if (categoryModalTitle) {
            categoryModalTitle.textContent = 'Edit Category';
        }
        if (originalCodeInput) {
            originalCodeInput.value = data.code;
        }
        if (depreciationCodeInput) {
            depreciationCodeInput.value = data.code;
        }
        setCodeEditableUi(false);
        if (descriptionInput) {
            descriptionInput.value = data.description;
        }
        if (monthsInput) {
            monthsInput.value = months > 0 ? months : '';
        }
        updateMonthsToYearsDisplay();
        if (glCodePickerInput) {
            glCodePickerInput.value = '';
        }
        clearGlSelection();
        renderGlDropdown(glCodesCache);
        if (glCodePickerInput && data.glCode) {
            var selected = null;
            for (var i = 0; i < glCodesCache.length; i++) {
                var item = glCodesCache[i] || {};
                if ((item.gl_code || '') === data.glCode) {
                    selected = item;
                    break;
                }
            }
            if (selected) {
                setGlSelection(selected);
            } else {
                glCodePickerInput.value = data.glCode;
                if (glCodeSelect) {
                    glCodeSelect.value = data.glCode;
                }
            }
        }
        closeGlDropdown();
    }

    function openCategoryModal() {
        if (typeof openModal === 'function') {
            openModal('category-modal');
        } else if (categoryModal) {
            categoryModal.classList.remove('hidden');
            categoryModal.classList.add('flex');
        }
    }

    function closeCategoryModal() {
        if (typeof closeModal === 'function') {
            closeModal('category-modal');
        } else if (categoryModal) {
            categoryModal.classList.add('hidden');
            categoryModal.classList.remove('flex');
        }
    }

    function openDeleteModal(code, description) {
        if (!deleteForm || !deleteHiddenFields || !deleteMessage) {
            return;
        }

        deleteForm.setAttribute('action', deleteAction);
        deleteHiddenFields.innerHTML =
            '<input type="hidden" name="depreciation_code" value="' + escapeHtml(code) + '">';
        deleteMessage.textContent = 'Are you sure you want to delete category "' + code + ' - ' + description + '"?';

        if (typeof openModal === 'function') {
            openModal('modal-delete-confirmation');
        } else if (deleteModal) {
            deleteModal.classList.remove('hidden');
            deleteModal.classList.add('flex');
        }
    }

    function loadGlCodes() {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', glCodesApiUrl, true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4) {
                return;
            }

            if (xhr.status < 200 || xhr.status >= 300) {
                return;
            }

            try {
                var codes = parseJsonResponse(xhr.responseText);
                if (!Array.isArray(codes)) {
                    throw new Error('Unexpected GL code payload format.');
                }

                glCodesCache = codes;
                renderGlDropdown(glCodesCache);
                closeGlDropdown();
            } catch (error) {
                console.error('Failed to load GL codes:', error);
            }
        };
        xhr.send();
    }

    function loadCategories() {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', categoriesApiUrl, true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4) {
                return;
            }

            if (xhr.status < 200 || xhr.status >= 300) {
                renderEmptyRow('Unable to load categories right now.');
                return;
            }

            try {
                var payload = parseJsonResponse(xhr.responseText);
                var rules = [];

                if (Array.isArray(payload)) {
                    rules = payload;
                } else if (payload && Array.isArray(payload.data)) {
                    rules = payload.data;
                } else if (payload && Array.isArray(payload.plRules)) {
                    rules = payload.plRules;
                } else {
                    throw new Error('Unexpected category payload format.');
                }

                renderCategories(rules);
            } catch (error) {
                console.error('Failed to load categories:', error);
                renderEmptyRow('Unable to parse category data.');
            }
        };
        xhr.send();
    }

    if (addBtn) {
        addBtn.addEventListener('click', function () {
            setAddMode();
            openCategoryModal();
        });
    }

    var closeButtons = categoryModal ? categoryModal.querySelectorAll('.close-modal') : [];
    for (var i = 0; i < closeButtons.length; i++) {
        closeButtons[i].addEventListener('click', closeCategoryModal);
    }

    categoriesTbody.addEventListener('click', function (event) {
        var editBtn = event.target.closest('.btn-edit-category');
        if (editBtn) {
            setEditMode({
                code: editBtn.getAttribute('data-code') || '',
                description: editBtn.getAttribute('data-description') || '',
                months: editBtn.getAttribute('data-months') || '',
                glCode: editBtn.getAttribute('data-gl-code') || ''
            });
            openCategoryModal();
            return;
        }

        var deleteBtn = event.target.closest('.btn-delete-category');
        if (deleteBtn) {
            openDeleteModal(
                deleteBtn.getAttribute('data-code') || '',
                deleteBtn.getAttribute('data-description') || ''
            );
        }
    });

    categoryForm.addEventListener('submit', function (event) {
        syncHiddenGlCodeFromPicker();
        if (glCodeSelect && !glCodeSelect.value) {
            event.preventDefault();
            alert('Please select a valid GL Account from the dropdown suggestions.');
            if (glCodePickerInput) {
                glCodePickerInput.focus();
            }
            return;
        }
    });

    if (monthsInput) {
        monthsInput.addEventListener('input', updateMonthsToYearsDisplay);
        monthsInput.addEventListener('change', updateMonthsToYearsDisplay);
    }

    if (glCodePickerInput && glCodeDropdown) {
        glCodePickerInput.addEventListener('focus', function () {
            var filtered = filterGlCodes(glCodePickerInput.value);
            renderGlDropdown(filtered);
        });

        glCodePickerInput.addEventListener('input', function () {
            var filtered = filterGlCodes(glCodePickerInput.value);
            renderGlDropdown(filtered);
            syncHiddenGlCodeFromPicker();
        });

        glCodePickerInput.addEventListener('change', syncHiddenGlCodeFromPicker);

        document.addEventListener('click', function (event) {
            var clickedInsideInput = event.target === glCodePickerInput;
            var clickedInsideDropdown = glCodeDropdown.contains(event.target);
            if (!clickedInsideInput && !clickedInsideDropdown) {
                closeGlDropdown();
            }
        });
    }

    setCodeEditableUi(true);
    updateMonthsToYearsDisplay();

    loadGlCodes();
    loadCategories();
});