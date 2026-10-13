document.addEventListener('DOMContentLoaded', function() {

    // Sidebar toggle (mobile)
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.admin-sidebar');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => sidebar.classList.toggle('open'));

        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768 && !sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    }

    // Delete confirmation modal
    const deleteModal = document.getElementById('delete-modal');
    const deleteForm = document.getElementById('delete-form');

    document.querySelectorAll('.btn-delete-trigger').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name || 'item ini';

            if (deleteForm) {
                deleteForm.querySelector('input[name="delete_id"]').value = id;
            }

            const modalText = deleteModal.querySelector('p');
            if (modalText) {
                modalText.textContent = 'Apakah Anda yakin ingin menghapus "' + name + '"? Tindakan ini tidak dapat dibatalkan.';
            }

            deleteModal.classList.add('show');
        });
    });

    if (deleteModal) {
        deleteModal.querySelector('.btn-cancel-modal')?.addEventListener('click', () => {
            deleteModal.classList.remove('show');
        });

        deleteModal.addEventListener('click', function(e) {
            if (e.target === this) this.classList.remove('show');
        });
    }

    // Table search filter
    const searchInput = document.querySelector('.admin-search input');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase();
            const rows = document.querySelectorAll('.data-table tbody tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }

    // File input label update
    document.querySelectorAll('input[type="file"]').forEach(input => {
        input.addEventListener('change', function() {
            const label = this.closest('.file-input-wrapper')?.querySelector('.file-input-text');
            if (label && this.files.length > 0) {
                label.textContent = this.files[0].name;
            }
        });
    });

    // Form validation
    document.querySelectorAll('.admin-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            let valid = true;

            this.querySelectorAll('[required]').forEach(field => {
                const errorEl = field.parentElement.querySelector('.form-error');

                if (!field.value.trim()) {
                    valid = false;
                    field.style.borderColor = 'var(--accent)';
                    if (errorEl) errorEl.textContent = 'Kolom ini wajib diisi';
                } else {
                    field.style.borderColor = '';
                    if (errorEl) errorEl.textContent = '';
                }
            });

            if (!valid) e.preventDefault();
        });
    });

});
