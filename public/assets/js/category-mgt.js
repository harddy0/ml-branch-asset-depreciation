var classificationsStore = {
    plRules: [],
    assetTypes: [],
    assetGroups: []
};

var activeState = {
    depreciationCode: null,
    assetCode: null
};

function escapeHtml(input) {
    return String(input ?? '').replace(/[&<>"']/g, function (char) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char];
    });
}

function normalizeResponseText(text) {
    return text.replace(/^\uFEFF/, '').trim();
}

function getApiUrl() {
    return BASE_URL + '/public/api/get_classifications.php';
}

function emptyState(title, description) {
    return '' +
        '<div class="h-full min-h-[320px] flex items-center justify-center">' +
            '<div class="text-center max-w-[260px]">' +
                '<div class="w-11 h-11 rounded-xl bg-slate-100 mx-auto mb-3 flex items-center justify-center">' +
                    '<svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
                        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h16M4 18h16"/>' +
                    '</svg>' +
                '</div>' +
                '<p class="text-sm font-bold text-slate-600">' + escapeHtml(title) + '</p>' +
                '<p class="text-xs font-medium text-slate-400 mt-1">' + escapeHtml(description) + '</p>' +
            '</div>' +
        '</div>';
}

function cardActions(onEdit, onDelete) {
    return '' +
        '<div class="flex items-center gap-1 shrink-0">' +
            '<button type="button" class="p-1.5 rounded-md text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors" data-action="edit" aria-label="Edit">' +
                '<svg class="w-3.5 h-3.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
                    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.5-7.5L9 14l-3 1 1-3 8.5-8.5a1.5 1.5 0 012 2z"/>' +
                '</svg>' +
            '</button>' +
            '<button type="button" class="p-1.5 rounded-md text-slate-400 hover:text-red-700 hover:bg-red-50 transition-colors" data-action="delete" aria-label="Delete">' +
                '<svg class="w-3.5 h-3.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
                    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16"/>' +
                '</svg>' +
            '</button>' +
        '</div>';
}

function renderPlRules() {
    var container = document.getElementById('pl-rules-container');
    if (!container) return;

    if (!classificationsStore.plRules.length) {
        container.innerHTML = emptyState('No P&L policies yet', 'Add a policy to start the hierarchy.');
        return;
    }

    container.innerHTML = classificationsStore.plRules.map(function (rule) {
        var isActive = activeState.depreciationCode === rule.depreciation_code;
        return '' +
            '<article class="js-pl-rule-card mb-2 border rounded-xl px-3 py-2.5 cursor-pointer transition-all ' + (isActive ? 'border-red-400 bg-red-50' : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50') + '" data-code="' + escapeHtml(rule.depreciation_code) + '">' +
                '<div class="flex items-start justify-between gap-2">' +
                    '<div>' +
                        '<p class="text-xs font-black font-mono text-slate-800">' + escapeHtml(rule.depreciation_code) + '</p>' +
                        '<p class="text-xs font-semibold text-slate-600 mt-1">' + escapeHtml(rule.description) + '</p>' +
                        '<p class="text-[11px] text-slate-500 mt-1">Limit: <span class="font-bold">' + escapeHtml(rule.limit_months) + '</span> mos | Rule: <span class="font-bold">' + escapeHtml(rule.rule_type) + '</span></p>' +
                    '</div>' +
                    cardActions() +
                '</div>' +
            '</article>';
    }).join('');

    container.querySelectorAll('.js-pl-rule-card').forEach(function (card) {
        var code = card.getAttribute('data-code');
        var rule = classificationsStore.plRules.find(function (item) { return item.depreciation_code === code; });
        if (!rule) return;

        card.addEventListener('click', function () {
            activeState.depreciationCode = code;
            activeState.assetCode = null;
            renderPlRules();
            renderAssetTypes();
            renderAssetGroups();
        });

        var editBtn = card.querySelector('[data-action="edit"]');
        var deleteBtn = card.querySelector('[data-action="delete"]');

        if (editBtn) {
            editBtn.addEventListener('click', function (event) {
                event.stopPropagation();
                openPlRuleEditModal(rule);
            });
        }

        if (deleteBtn) {
            deleteBtn.addEventListener('click', function (event) {
                event.stopPropagation();
                openDeleteModal({
                    action: BASE_URL + '/public/actions/pl_rule_delete.php',
                    hiddenFields: [{ name: 'depreciation_code', value: rule.depreciation_code }],
                    message: 'Delete P&L policy ' + rule.depreciation_code + '? This cannot be undone.'
                });
            });
        }
    });
}

function getAssetTypesForActiveRule() {
    if (!activeState.depreciationCode) return [];
    return classificationsStore.assetTypes.filter(function (assetType) {
        return assetType.depreciation_code === activeState.depreciationCode;
    });
}

function renderAssetTypes() {
    var container = document.getElementById('asset-types-container');
    if (!container) return;

    if (!activeState.depreciationCode) {
        container.innerHTML = emptyState('Select a policy', 'Select a P&L policy to view asset types.');
        return;
    }

    var records = getAssetTypesForActiveRule();
    if (!records.length) {
        container.innerHTML = emptyState('No asset types', 'No asset types are linked to this P&L policy yet.');
        return;
    }

    container.innerHTML = records.map(function (assetType) {
        var isActive = activeState.assetCode === assetType.asset_code;
        return '' +
            '<article class="js-asset-type-card mb-2 border rounded-xl px-3 py-2.5 cursor-pointer transition-all ' + (isActive ? 'border-red-400 bg-red-50' : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50') + '" data-code="' + escapeHtml(assetType.asset_code) + '">' +
                '<div class="flex items-start justify-between gap-2">' +
                    '<div>' +
                        '<p class="text-xs font-black font-mono text-slate-800">' + escapeHtml(assetType.asset_code) + '</p>' +
                        '<p class="text-xs font-semibold text-slate-600 mt-1">' + escapeHtml(assetType.asset_name) + '</p>' +
                    '</div>' +
                    cardActions() +
                '</div>' +
            '</article>';
    }).join('');

    container.querySelectorAll('.js-asset-type-card').forEach(function (card) {
        var code = card.getAttribute('data-code');
        var record = classificationsStore.assetTypes.find(function (item) { return item.asset_code === code; });
        if (!record) return;

        card.addEventListener('click', function () {
            activeState.assetCode = code;
            renderAssetTypes();
            renderAssetGroups();
        });

        var editBtn = card.querySelector('[data-action="edit"]');
        var deleteBtn = card.querySelector('[data-action="delete"]');

        if (editBtn) {
            editBtn.addEventListener('click', function (event) {
                event.stopPropagation();
                openAssetTypeEditModal(record);
            });
        }

        if (deleteBtn) {
            deleteBtn.addEventListener('click', function (event) {
                event.stopPropagation();
                openDeleteModal({
                    action: BASE_URL + '/public/actions/asset_type_delete.php',
                    hiddenFields: [{ name: 'asset_code', value: record.asset_code }],
                    message: 'Delete asset type ' + record.asset_code + '? This cannot be undone.'
                });
            });
        }
    });
}

function getAssetGroupsForActiveAssetType() {
    if (!activeState.assetCode) return [];
    return classificationsStore.assetGroups.filter(function (group) {
        return group.asset_code === activeState.assetCode;
    });
}

function renderAssetGroups() {
    var container = document.getElementById('asset-groups-container');
    if (!container) return;

    if (!activeState.depreciationCode) {
        container.innerHTML = emptyState('Select a policy', 'Select a P&L policy and then an asset type to view groups.');
        return;
    }

    if (!activeState.assetCode) {
        container.innerHTML = emptyState('Select an asset type', 'Select an asset type to view asset groups.');
        return;
    }

    var records = getAssetGroupsForActiveAssetType();
    if (!records.length) {
        container.innerHTML = emptyState('No asset groups', 'No groups are linked to this asset type yet.');
        return;
    }

    container.innerHTML = records.map(function (group) {
        return '' +
            '<article class="mb-2 border border-slate-200 rounded-xl px-3 py-2.5 hover:border-slate-300 hover:bg-slate-50 transition-all">' +
                '<div class="flex items-start justify-between gap-2">' +
                    '<div>' +
                        '<p class="text-xs font-black font-mono text-slate-800">' + escapeHtml(group.group_code) + '</p>' +
                        '<p class="text-xs font-semibold text-slate-600 mt-1">' + escapeHtml(group.group_name) + '</p>' +
                        '<p class="text-[11px] text-slate-500 mt-1">Actual Months: <span class="font-bold">' + escapeHtml(group.actual_months) + '</span></p>' +
                    '</div>' +
                    cardActions() +
                '</div>' +
            '</article>';
    }).join('');

    container.querySelectorAll('article').forEach(function (card, index) {
        var record = records[index];
        if (!record) return;

        var editBtn = card.querySelector('[data-action="edit"]');
        var deleteBtn = card.querySelector('[data-action="delete"]');

        if (editBtn) {
            editBtn.addEventListener('click', function () {
                openCategoryEditModal(record);
            });
        }

        if (deleteBtn) {
            deleteBtn.addEventListener('click', function () {
                openDeleteModal({
                    action: BASE_URL + '/public/actions/category_delete.php',
                    hiddenFields: [{ name: 'id', value: record.id }],
                    message: 'Delete asset group ' + record.group_code + '? This cannot be undone.'
                });
            });
        }
    });
}

function buildOptions(list, valueKey, labelKey, placeholderText, selectedValue) {
    var html = '<option value="">' + escapeHtml(placeholderText) + '</option>';
    html += list.map(function (item) {
        var value = String(item[valueKey] ?? '');
        var label = String(item[labelKey] ?? value);
        var selected = String(selectedValue ?? '') === value ? ' selected' : '';
        return '<option value="' + escapeHtml(value) + '"' + selected + '>' + escapeHtml(value + ' - ' + label) + '</option>';
    }).join('');
    return html;
}

function populatePlRuleSelects(selectedValue) {
    var options = buildOptions(
        classificationsStore.plRules,
        'depreciation_code',
        'description',
        'Select P&L policy',
        selectedValue
    );

    document.querySelectorAll('.js-pl-rule-select').forEach(function (select) {
        select.innerHTML = options;
    });
}

function populateAssetTypeSelects(selectedValue, restrictToActiveRule) {
    var source = classificationsStore.assetTypes;

    if (restrictToActiveRule && activeState.depreciationCode) {
        source = source.filter(function (item) {
            return item.depreciation_code === activeState.depreciationCode;
        });
    }

    var options = buildOptions(
        source,
        'asset_code',
        'asset_name',
        'Select asset type',
        selectedValue
    );

    document.querySelectorAll('.js-asset-type-select').forEach(function (select) {
        select.innerHTML = options;
    });
}

function buildAutoGroupCode(assetCode, actualMonths) {
    var months = parseInt(actualMonths, 10);
    if (!assetCode || !months || months < 1) return '';

    var assetType = classificationsStore.assetTypes.find(function (item) {
        return item.asset_code === assetCode;
    });

    var rawName = assetType && assetType.asset_name
        ? String(assetType.asset_name).trim()
        : String(assetCode).trim();

    var words = rawName
        .split(/\s+/)
        .map(function (word) { return word.replace(/[^A-Za-z0-9]/g, ''); })
        .filter(Boolean);

    var baseName = '';
    if (words.length >= 2) {
        baseName = words
            .map(function (word) { return word.charAt(0); })
            .join('')
            .toUpperCase();

        if (baseName.length > 4) {
            baseName = baseName.substring(0, 4);
        }
    } else if (words.length === 1) {
        baseName = words[0].substring(0, 3).toUpperCase();
    }

    if (!baseName) {
        baseName = String(assetCode).replace(/[^A-Za-z0-9]/g, '').toUpperCase().substring(0, 3);
    }

    var suffix = String(months) + 'MOS';
    var maxBaseLength = 50 - suffix.length;
    if (maxBaseLength < 1) return suffix;

    return baseName.substring(0, maxBaseLength) + suffix;
}

function buildAutoGroupName(assetCode, actualMonths) {
    var months = parseInt(actualMonths, 10);
    if (!assetCode || !months || months < 1) return '';

    var assetType = classificationsStore.assetTypes.find(function (item) {
        return item.asset_code === assetCode;
    });

    var baseName = assetType && assetType.asset_name
        ? String(assetType.asset_name).trim()
        : String(assetCode).trim();

    return baseName + ' (' + months + 'mos)';
}

function refreshAddCategoryCodeAutofill() {
    var groupCodeInput = document.getElementById('category-add-group-code');
    var groupNameInput = document.getElementById('category-add-group-name');
    var monthsInput = document.getElementById('category-add-actual-months');
    var assetCodeSelect = document.getElementById('category-add-asset-code');

    if (!groupCodeInput || !groupNameInput || !monthsInput || !assetCodeSelect) return;

    var generatedName = buildAutoGroupName(assetCodeSelect.value, monthsInput.value);
    if (groupNameInput.dataset.manualEdit !== '1') {
        groupNameInput.value = generatedName;
    }

    if (groupCodeInput.dataset.manualEdit === '1') return;

    var generatedCode = buildAutoGroupCode(assetCodeSelect.value, monthsInput.value);
    if (!generatedCode) {
        groupCodeInput.value = '';
        return;
    }

    groupCodeInput.value = generatedCode;
}

function initializeAddCategoryAutofill() {
    var groupCodeInput = document.getElementById('category-add-group-code');
    var groupNameInput = document.getElementById('category-add-group-name');
    var monthsInput = document.getElementById('category-add-actual-months');
    var assetCodeSelect = document.getElementById('category-add-asset-code');

    if (!groupCodeInput || !groupNameInput || !monthsInput || !assetCodeSelect) return;

    groupCodeInput.dataset.manualEdit = '0';
    groupNameInput.dataset.manualEdit = '0';

    groupCodeInput.addEventListener('input', function () {
        groupCodeInput.dataset.manualEdit = '1';
    });

    groupNameInput.addEventListener('input', function () {
        groupNameInput.dataset.manualEdit = '1';
    });

    assetCodeSelect.addEventListener('change', refreshAddCategoryCodeAutofill);
    monthsInput.addEventListener('input', refreshAddCategoryCodeAutofill);
}

function openPlRuleEditModal(rule) {
    document.getElementById('pl-rule-edit-original-code').value = rule.depreciation_code;
    document.getElementById('pl-rule-edit-code').value = rule.depreciation_code;
    document.getElementById('pl-rule-edit-description').value = rule.description;
    document.getElementById('pl-rule-edit-limit').value = rule.limit_months;
    document.getElementById('pl-rule-edit-type').value = rule.rule_type;
    openModal('modal-edit-pl-rule');
}

function openAssetTypeEditModal(record) {
    populatePlRuleSelects(record.depreciation_code);

    document.getElementById('asset-type-edit-original-code').value = record.asset_code;
    document.getElementById('asset-type-edit-code').value = record.asset_code;
    document.getElementById('asset-type-edit-name').value = record.asset_name;
    document.getElementById('asset-type-edit-depreciation-code').value = record.depreciation_code;
    openModal('modal-edit-asset-type');
}

function openCategoryEditModal(record) {
    populateAssetTypeSelects(record.asset_code, false);

    document.getElementById('category-edit-id').value = record.id;
    document.getElementById('category-edit-group-code').value = record.group_code;
    document.getElementById('category-edit-group-name').value = record.group_name;
    document.getElementById('category-edit-actual-months').value = record.actual_months;
    document.getElementById('category-edit-asset-code').value = record.asset_code;
    openModal('modal-edit-category');
}

function openDeleteModal(config) {
    var form = document.getElementById('delete-confirm-form');
    var hiddenFieldsContainer = document.getElementById('delete-hidden-fields');
    var message = document.getElementById('delete-modal-message');
    if (!form || !hiddenFieldsContainer || !message) return;

    form.setAttribute('action', config.action || '#');
    message.textContent = config.message || 'Are you sure you want to delete this record?';
    hiddenFieldsContainer.innerHTML = '';

    (config.hiddenFields || []).forEach(function (field) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = field.name;
        input.value = field.value;
        hiddenFieldsContainer.appendChild(input);
    });

    openModal('modal-delete-confirmation');
}

function attachAddButtons() {
    var addPlRuleBtn = document.getElementById('btn-add-pl-rule');
    var addAssetTypeBtn = document.getElementById('btn-add-asset-type');
    var addCategoryBtn = document.getElementById('btn-add-category');

    if (addPlRuleBtn) {
        addPlRuleBtn.addEventListener('click', function () {
            openModal('modal-add-pl-rule');
        });
    }

    if (addAssetTypeBtn) {
        addAssetTypeBtn.addEventListener('click', function () {
            populatePlRuleSelects(activeState.depreciationCode || '');
            openModal('modal-add-asset-type');
        });
    }

    if (addCategoryBtn) {
        addCategoryBtn.addEventListener('click', function () {
            populateAssetTypeSelects(activeState.assetCode || '', true);
            var groupCodeInput = document.getElementById('category-add-group-code');
            var groupNameInput = document.getElementById('category-add-group-name');
            var monthsInput = document.getElementById('category-add-actual-months');
            if (groupCodeInput) {
                groupCodeInput.dataset.manualEdit = '0';
            }
            if (groupNameInput) {
                groupNameInput.dataset.manualEdit = '0';
            }
            if (monthsInput) {
                monthsInput.value = '';
            }
            refreshAddCategoryCodeAutofill();
            openModal('modal-add-category');
            if (monthsInput) {
                monthsInput.focus();
            }
        });
    }
}

function attachCodeInputFilters() {
    document.querySelectorAll('.js-code-input').forEach(function (element) {
        element.addEventListener('input', function () {
            var cursor = this.selectionStart;
            this.value = this.value.toUpperCase().replace(/[^A-Z0-9_-]/g, '');
            this.setSelectionRange(cursor, cursor);
        });
    });
}

async function loadClassifications() {
    var response = await fetch(getApiUrl(), {
        method: 'GET',
        headers: {
            'Accept': 'application/json'
        }
    });

    if (!response.ok) {
        throw new Error('Failed to load hierarchy data.');
    }

    var rawText = await response.text();
    var cleaned = normalizeResponseText(rawText);
    var data = JSON.parse(cleaned);

    if (data.error) {
        throw new Error(data.error);
    }

    classificationsStore.plRules = Array.isArray(data.plRules) ? data.plRules : [];
    classificationsStore.assetTypes = Array.isArray(data.assetTypes) ? data.assetTypes : [];
    classificationsStore.assetGroups = Array.isArray(data.assetGroups) ? data.assetGroups : [];
}

function renderErrorState(message) {
    var text = message || 'Unable to load hierarchy data.';
    var markup = emptyState('Load failed', text);

    var plContainer = document.getElementById('pl-rules-container');
    var atContainer = document.getElementById('asset-types-container');
    var agContainer = document.getElementById('asset-groups-container');

    if (plContainer) plContainer.innerHTML = markup;
    if (atContainer) atContainer.innerHTML = markup;
    if (agContainer) agContainer.innerHTML = markup;
}

async function initializeCategoryManagement() {
    attachAddButtons();
    attachCodeInputFilters();
    initializeAddCategoryAutofill();

    try {
        await loadClassifications();

        populatePlRuleSelects(activeState.depreciationCode || '');
        populateAssetTypeSelects(activeState.assetCode || '', false);

        renderPlRules();
        renderAssetTypes();
        renderAssetGroups();
    } catch (error) {
        renderErrorState(error.message);
    }
}

document.addEventListener('DOMContentLoaded', initializeCategoryManagement);