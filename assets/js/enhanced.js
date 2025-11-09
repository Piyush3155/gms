/**
 * Enhanced JavaScript Functionality for GMS
 * Advanced features, utilities, and interactions
 */

// ============================================
// DATA TABLE ENHANCEMENTS
// ============================================

/**
 * DataTable - Advanced Table Component
 * 
 * Features:
 * - Searchable data with real-time filtering
 * - Sortable columns (click on headers)
 * - Pagination with customizable items per page
 * - Export to CSV, Excel, and PDF
 * - Responsive design
 * 
 * Usage Example:
 * 
 * HTML:
 * <table id="my-table" class="table table-modern">
 *   <thead>
 *     <tr>
 *       <th>Name</th>
 *       <th>Email</th>
 *       <th data-sortable="false">Actions</th>
 *     </tr>
 *   </thead>
 *   <tbody>
 *     <tr><td>John Doe</td><td>john@example.com</td><td><button>Edit</button></td></tr>
 *   </tbody>
 * </table>
 * 
 * JavaScript:
 * const table = document.getElementById('my-table');
 * new DataTable(table, {
 *     searchable: true,      // Enable search field
 *     pagination: true,      // Enable pagination
 *     sortable: true,        // Enable column sorting
 *     exportable: true,      // Enable export buttons
 *     itemsPerPage: 10,      // Number of rows per page
 *     exportOptions: {
 *         fileName: 'my-export'  // Base filename for exports
 *     }
 * });
 * 
 * Required Libraries for Export:
 * - XLSX: https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js
 * - jsPDF: https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js
 * - jsPDF-AutoTable: https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js
 */

class DataTable {
    constructor(tableElement, options = {}) {
        // Handle both ID string and DOM element
        if (typeof tableElement === 'string') {
            this.table = document.getElementById(tableElement);
        } else {
            this.table = tableElement;
        }
        
        if (!this.table) {
            console.error("DataTable: Provided table element is invalid.");
            return;
        }

        this.options = {
            sortable: true,
            searchable: true,
            pagination: true,
            itemsPerPage: 10,
            exportable: false,
            exportOptions: {
                fileName: 'data-export',
            },
            ...options
        };

        this.currentPage = 1;
        this.sortColumn = null;
        this.sortDirection = 'asc';
        this.fullData = this.parseTableData();
        this.filteredData = this.fullData;

        this.init();
    }

    init() {
        console.log('[DataTable] Initializing...');
        console.log('[DataTable] Table element:', this.table);
        console.log('[DataTable] Table parent:', this.table.parentNode);
        
        // 1. Create wrapper
        this.wrapper = document.createElement('div');
        this.wrapper.className = 'data-table-wrapper';
        console.log('[DataTable] Wrapper created');
        
        // 2. Insert wrapper into DOM (before table)
        this.table.parentNode.insertBefore(this.wrapper, this.table);
        console.log('[DataTable] Wrapper inserted into DOM');
        
        // 3. Move table INTO wrapper ← This is critical
        this.wrapper.appendChild(this.table);
        console.log('[DataTable] Table moved into wrapper');
        
        // 4. Create header (now table is inside wrapper, so insertBefore works)
        this.createHeader();
        console.log('[DataTable] Header created');
        
        // 5. Initialize sorting if enabled
        if (this.options.sortable) {
            this.initSorting();
            console.log('[DataTable] Sorting initialized');
        }
        
        // 6. Create footer
        this.createFooter();
        console.log('[DataTable] Footer created');
        
        // 7. Render initial state
        this.render();
        console.log('[DataTable] Initial render complete');
    }

    createHeader() {
        const headerContainer = document.createElement('div');
        headerContainer.className = 'table-header-actions';

        // Create search container
        if (this.options.searchable) {
            const searchContainer = document.createElement('div');
            searchContainer.className = 'search-container';
            searchContainer.innerHTML = `
                <i class="bi bi-search"></i>
                <input type="text" class="form-control" placeholder="Search...">
            `;
            headerContainer.appendChild(searchContainer);

            const searchInput = searchContainer.querySelector('input');
            searchInput.addEventListener('input', (e) => {
                this.currentPage = 1;
                this.filter(e.target.value);
            });
        }

        // Create export dropdown
        if (this.options.exportable) {
            const exportContainer = document.createElement('div');
            exportContainer.className = 'export-container';
            exportContainer.innerHTML = `
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-download me-1"></i> Export
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#" data-format="csv"><i class="bi bi-filetype-csv me-2"></i>Export as CSV</a></li>
                        <li><a class="dropdown-item" href="#" data-format="excel"><i class="bi bi-file-earmark-excel me-2"></i>Export as Excel</a></li>
                        <li><a class="dropdown-item" href="#" data-format="pdf"><i class="bi bi-file-earmark-pdf me-2"></i>Export as PDF</a></li>
                    </ul>
                </div>
            `;
            headerContainer.appendChild(exportContainer);

            exportContainer.querySelectorAll('.dropdown-item').forEach(item => {
                item.addEventListener('click', (e) => {
                    e.preventDefault();
                    const format = e.currentTarget.dataset.format;
                    this.export(format);
                });
            });
        }
        
        this.wrapper.insertBefore(headerContainer, this.table);
    }

    createFooter() {
        this.footer = document.createElement('div');
        this.footer.className = 'pagination-controls mt-3 d-flex justify-content-between align-items-center';
        this.wrapper.appendChild(this.footer);
    }

    parseTableData() {
        const rows = Array.from(this.table.querySelectorAll('tbody tr'));
        return rows.map(row => {
            const cells = Array.from(row.querySelectorAll('td'));
            return cells.map(cell => cell.innerHTML);
        });
    }

    render() {
        this.renderTableBody();
        if (this.options.pagination) {
            this.updatePagination();
        }
    }

    renderTableBody() {
        const tbody = this.table.querySelector('tbody');
        tbody.innerHTML = '';

        const pageData = this.options.pagination ?
            this.filteredData.slice((this.currentPage - 1) * this.options.itemsPerPage, this.currentPage * this.options.itemsPerPage) :
            this.filteredData;

        if (pageData.length === 0) {
            const colCount = this.table.querySelector('thead tr').children.length;
            tbody.innerHTML = `<tr><td colspan="${colCount}" class="text-center">No data available</td></tr>`;
            return;
        }

        pageData.forEach(rowData => {
            const tr = document.createElement('tr');
            rowData.forEach(cellData => {
                const td = document.createElement('td');
                td.innerHTML = cellData;
                tr.appendChild(td);
            });
            tbody.appendChild(tr);
        });
    }

    initSorting() {
        const headers = this.table.querySelectorAll('thead th');
        headers.forEach((header, index) => {
            // Make sortable unless explicitly marked as false
            const isSortable = !header.hasAttribute('data-sortable') || header.getAttribute('data-sortable') !== 'false';
            
            if (isSortable) {
                header.style.cursor = 'pointer';
                header.innerHTML += ' <i class="bi bi-arrow-down-up"></i>';

                header.addEventListener('click', () => {
                    this.sort(index, header);
                });
            }
        });
    }

    sort(columnIndex, headerElement) {
        if (this.sortColumn === columnIndex) {
            this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            this.sortDirection = 'asc';
            this.sortColumn = columnIndex;
        }

        this.table.querySelectorAll('thead th i').forEach(icon => {
            icon.className = 'bi bi-arrow-down-up';
        });
        const icon = headerElement.querySelector('i');
        icon.className = this.sortDirection === 'asc' ? 'bi bi-sort-up' : 'bi bi-sort-down';

        this.filteredData.sort((a, b) => {
            const aValue = this.getTextContent(a[columnIndex]);
            const bValue = this.getTextContent(b[columnIndex]);

            const aNum = parseFloat(aValue.replace(/[^0-9.-]/g, ''));
            const bNum = parseFloat(bValue.replace(/[^0-9.-]/g, ''));

            if (!isNaN(aNum) && !isNaN(bNum)) {
                return this.sortDirection === 'asc' ? aNum - bNum : bNum - aNum;
            }

            return this.sortDirection === 'asc' ?
                aValue.localeCompare(bValue, undefined, { numeric: true }) :
                bValue.localeCompare(aValue, undefined, { numeric: true });
        });

        this.render();
    }

    filter(query) {
        const lowerQuery = query.toLowerCase();
        this.filteredData = this.fullData.filter(row => {
            return row.some(cell => this.getTextContent(cell).toLowerCase().includes(lowerQuery));
        });
        this.render();
    }

    updatePagination() {
        const totalPages = Math.ceil(this.filteredData.length / this.options.itemsPerPage);
        if (totalPages <= 1 && !this.options.searchable) {
            this.footer.innerHTML = '';
            return;
        }

        const start = (this.currentPage - 1) * this.options.itemsPerPage + 1;
        const end = Math.min(this.currentPage * this.options.itemsPerPage, this.filteredData.length);
        const info = `
            <div class="pagination-info">
                Showing <strong>${start}</strong> to <strong>${end}</strong> of <strong>${this.filteredData.length}</strong> entries
            </div>
        `;

        let paginationHTML = `<nav><ul class="pagination mb-0">`;
        paginationHTML += `
            <li class="page-item ${this.currentPage === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${this.currentPage - 1}">&laquo;</a>
            </li>
        `;

        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= this.currentPage - 1 && i <= this.currentPage + 1)) {
                paginationHTML += `
                    <li class="page-item ${i === this.currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `;
            } else if (i === this.currentPage - 2 || i === this.currentPage + 2) {
                paginationHTML += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }

        paginationHTML += `
            <li class="page-item ${this.currentPage === totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${this.currentPage + 1}">&raquo;</a>
            </li>
        </ul></nav>`;

        this.footer.innerHTML = info + paginationHTML;

        this.footer.querySelectorAll('a.page-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const page = parseInt(link.dataset.page);
                if (page > 0 && page <= totalPages) {
                    this.currentPage = page;
                    this.render();
                }
            });
        });
    }

    export(format) {
        const headers = Array.from(this.table.querySelectorAll('thead th'))
            .map(th => this.getTextContent(th.innerHTML))
            .filter(h => h.toLowerCase() !== 'actions');
            
        const data = this.filteredData.map(row => {
            return row.slice(0, headers.length).map(cell => this.getTextContent(cell));
        });

        const fileName = `${this.options.exportOptions.fileName}_${new Date().toISOString().slice(0,10)}`;

        switch (format) {
            case 'csv':
                this.exportCSV(headers, data, fileName);
                break;
            case 'excel':
                this.exportExcel(headers, data, fileName);
                break;
            case 'pdf':
                this.exportPDF(headers, data, fileName);
                break;
        }
    }

    exportCSV(headers, data, fileName) {
        let csvContent = "data:text/csv;charset=utf-8,";
        csvContent += headers.join(",") + "\r\n";
        data.forEach(row => {
            csvContent += row.join(",") + "\r\n";
        });
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", `${fileName}.csv`);
        document.body.appendChild(link);
        link.click();
        link.remove();
    }

    exportExcel(headers, data, fileName) {
        if (typeof XLSX === 'undefined') {
            console.error('XLSX library is not loaded.');
            return;
        }
        const ws = XLSX.utils.aoa_to_sheet([headers, ...data]);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Sheet1');
        XLSX.writeFile(wb, `${fileName}.xlsx`);
    }

    exportPDF(headers, data, fileName) {
        if (typeof jspdf === 'undefined' || typeof jspdf.jsPDF === 'undefined') {
            console.error('jsPDF library is not loaded.');
            return;
        }
        const { jsPDF } = jspdf;
        const doc = new jsPDF();
        doc.autoTable({
            head: [headers],
            body: data,
            didDrawPage: function (data) {
                doc.setFontSize(18);
                doc.text(fileName.replace(/_/g, ' '), data.settings.margin.left, 15);
            }
        });
        doc.save(`${fileName}.pdf`);
    }

    getTextContent(html) {
        const temp = document.createElement('div');
        temp.innerHTML = html;
        return temp.textContent || temp.innerText || '';
    }
}
// ... existing code ...

// ============================================
// FORM VALIDATION ENHANCEMENTS
// ============================================

class FormValidator {
    constructor(formId) {
        this.form = document.getElementById(formId);
        if (!this.form) return;

        this.init();
    }

    init() {
        this.form.addEventListener('submit', (e) => {
            if (!this.validate()) {
                e.preventDefault();
                e.stopPropagation();
            }
            this.form.classList.add('was-validated');
        });

        // Real-time validation
        this.form.querySelectorAll('input, select, textarea').forEach(field => {
            field.addEventListener('blur', () => {
                this.validateField(field);
            });
        });
    }

    validate() {
        let isValid = true;
        const fields = this.form.querySelectorAll('input, select, textarea');
        
        fields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });

        return isValid;
    }

    validateField(field) {
        let isValid = true;
        const value = field.value.trim();

        // Required validation
        if (field.hasAttribute('required') && !value) {
            this.showError(field, 'This field is required');
            isValid = false;
        }
        // Email validation
        else if (field.type === 'email' && value && !this.isValidEmail(value)) {
            this.showError(field, 'Please enter a valid email address');
            isValid = false;
        }
        // Min length validation
        else if (field.hasAttribute('minlength') && value.length < field.getAttribute('minlength')) {
            this.showError(field, `Minimum ${field.getAttribute('minlength')} characters required`);
            isValid = false;
        }
        // Pattern validation
        else if (field.hasAttribute('pattern') && value && !new RegExp(field.getAttribute('pattern')).test(value)) {
            this.showError(field, field.getAttribute('title') || 'Invalid format');
            isValid = false;
        }
        else {
            this.clearError(field);
        }

        return isValid;
    }

    isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    showError(field, message) {
        field.classList.add('is-invalid');
        field.classList.remove('is-valid');
        
        let feedback = field.parentElement.querySelector('.invalid-feedback');
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            field.parentElement.appendChild(feedback);
        }
        feedback.textContent = message;
    }

    clearError(field) {
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');
        
        const feedback = field.parentElement.querySelector('.invalid-feedback');
        if (feedback) {
            feedback.remove();
        }
    }
}

// ============================================
// MODAL MANAGER
// ============================================

class ModalManager {
    static show(title, content, options = {}) {
        const modalId = 'dynamicModal' + Date.now();
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = modalId;
        modal.innerHTML = `
            <div class="modal-dialog ${options.size || 'modal-lg'}">
                <div class="modal-content modal-modern">
                    <div class="modal-header">
                        <h5 class="modal-title">${title}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">${content}</div>
                    ${options.footer ? `<div class="modal-footer">${options.footer}</div>` : ''}
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();

        modal.addEventListener('hidden.bs.modal', () => {
            modal.remove();
        });

        return bsModal;
    }

    static confirm(message, onConfirm, onCancel) {
        const footer = `
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="confirmBtn">Confirm</button>
        `;

        const modal = this.show('Confirm Action', message, { footer, size: 'modal-sm' });
        
        setTimeout(() => {
            const confirmBtn = document.getElementById('confirmBtn');
            if (confirmBtn) {
                confirmBtn.addEventListener('click', () => {
                    if (onConfirm) onConfirm();
                    modal.hide();
                });
            }
        }, 100);
    }

    static alert(message, type = 'info') {
        const icons = {
            success: 'fa-check-circle text-success',
            error: 'fa-exclamation-circle text-danger',
            warning: 'fa-exclamation-triangle text-warning',
            info: 'fa-info-circle text-info'
        };

        const content = `
            <div class="text-center py-4">
                <i class="fas ${icons[type]} fa-3x mb-3"></i>
                <p class="lead">${message}</p>
            </div>
        `;

        this.show('Notification', content, { size: 'modal-sm' });
    }
}

// ============================================
// TOAST NOTIFICATIONS
// ============================================

class ToastNotification {
    static show(message, type = 'success', duration = 3000) {
        const toastContainer = this.getContainer();
        const toast = document.createElement('div');
        toast.className = `toast-notification alert alert-${type} alert-dismissible fade show`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas ${this.getIcon(type)} me-2"></i>
                <div>${message}</div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        toastContainer.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }

    static getContainer() {
        let container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.style.cssText = `
                position: fixed;
                top: 80px;
                right: 20px;
                z-index: 9999;
                min-width: 300px;
                max-width: 400px;
            `;
            document.body.appendChild(container);
        }
        return container;
    }

    static getIcon(type) {
        const icons = {
            success: 'fa-check-circle',
            danger: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };
        return icons[type] || icons.info;
    }
}

// ============================================
// SCROLL ANIMATIONS
// ============================================

class ScrollAnimations {
    constructor() {
        this.observer = new IntersectionObserver(
            (entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                        this.observer.unobserve(entry.target);
                    }
                });
            },
            {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            }
        );

        this.init();
    }

    init() {
        document.querySelectorAll('.scroll-fade-in, .scroll-scale-in').forEach(el => {
            this.observer.observe(el);
        });
    }
}

// ============================================
// LIVE SEARCH
// ============================================

class LiveSearch {
    constructor(inputId, resultsId, searchFn, options = {}) {
        this.input = document.getElementById(inputId);
        this.resultsDiv = document.getElementById(resultsId);
        this.searchFn = searchFn;
        this.options = {
            minChars: 2,
            debounce: 300,
            ...options
        };
        
        if (this.input && this.resultsDiv) {
            this.init();
        }
    }

    init() {
        let timeout;
        
        this.input.addEventListener('input', (e) => {
            clearTimeout(timeout);
            const query = e.target.value.trim();

            if (query.length < this.options.minChars) {
                this.resultsDiv.style.display = 'none';
                return;
            }

            timeout = setTimeout(() => {
                this.search(query);
            }, this.options.debounce);
        });

        // Hide results when clicking outside
        document.addEventListener('click', (e) => {
            if (!this.input.contains(e.target) && !this.resultsDiv.contains(e.target)) {
                this.resultsDiv.style.display = 'none';
            }
        });
    }

    async search(query) {
        this.showLoading();
        
        try {
            const results = await this.searchFn(query);
            this.displayResults(results);
        } catch (error) {
            console.error('Search error:', error);
            this.resultsDiv.style.display = 'none';
        }
    }

    showLoading() {
        this.resultsDiv.innerHTML = `
            <div class="p-3 text-center">
                <div class="loading-spinner"></div>
                <p class="mt-2 mb-0 text-muted">Searching...</p>
            </div>
        `;
        this.resultsDiv.style.display = 'block';
    }

    displayResults(results) {
        if (!results || results.length === 0) {
            this.resultsDiv.innerHTML = '<div class="no-results p-3 text-center text-muted">No results found</div>';
        } else {
            this.resultsDiv.innerHTML = results.map(result => this.options.renderResult(result)).join('');
        }
        this.resultsDiv.style.display = 'block';
    }
}

// ============================================
// CLIPBOARD UTILITY
// ============================================

class ClipboardManager {
    static copy(text, successMessage = 'Copied to clipboard!') {
        navigator.clipboard.writeText(text).then(() => {
            ToastNotification.show(successMessage, 'success');
        }).catch(err => {
            ToastNotification.show('Failed to copy', 'danger');
            console.error('Copy failed:', err);
        });
    }
}

// ============================================
// CHART HELPER
// ============================================

class ChartHelper {
    static createLineChart(canvasId, labels, data, label = 'Data') {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return null;

        return new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: label,
                    data: data,
                    borderColor: 'rgb(99, 102, 241)',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    static createBarChart(canvasId, labels, data, label = 'Data') {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return null;

        return new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: label,
                    data: data,
                    backgroundColor: [
                        'rgba(99, 102, 241, 0.8)',
                        'rgba(14, 165, 233, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(239, 68, 68, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    static createDoughnutChart(canvasId, labels, data) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return null;

        return new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: [
                        'rgba(99, 102, 241, 0.8)',
                        'rgba(14, 165, 233, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(239, 68, 68, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
}

// ============================================
// INIT ON DOM READY
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    // Initialize scroll animations
    new ScrollAnimations();

    // Make utilities globally available
    window.DataTable = DataTable;
    window.FormValidator = FormValidator;
    window.ModalManager = ModalManager;
    window.ToastNotification = ToastNotification;
    window.ClipboardManager = ClipboardManager;
    window.ChartHelper = ChartHelper;
    window.LiveSearch = LiveSearch;
});
