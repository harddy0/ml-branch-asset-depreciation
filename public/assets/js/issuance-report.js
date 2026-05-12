// issuance-report.js
// Client-side wiring for Issuance Report page: datepickers and filter clearing
(function(){
    try {
        if (typeof flatpickr !== 'undefined') {
            flatpickr('input[name="date_from"]', { dateFormat: 'Y-m-d' });
            flatpickr('input[name="date_to"]', { dateFormat: 'Y-m-d' });
        }
    } catch(e){ console.warn(e); }

    // Clear filters button
    document.addEventListener('DOMContentLoaded', function(){
        const form = document.getElementById('filterForm');
        const clearBtn = document.getElementById('clearFiltersBtn');
        if(clearBtn && form){
            clearBtn.addEventListener('click', function(){
                form.reset();
                ['zoneSelect','regionSelect','branchSelect','categorySelect'].forEach(function(id){
                    const el = document.getElementById(id);
                    if(el){ el.value = ''; el.dispatchEvent(new Event('change',{ bubbles: true })); }
                });
            });
        }
    });
})();
