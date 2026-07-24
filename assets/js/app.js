(() => {
  document.querySelectorAll('[data-alert] button').forEach((button) => {
    button.addEventListener('click', () => button.parentElement.remove());
  });

  document.querySelectorAll('[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      const message = form.getAttribute('data-confirm') || 'Hapus data ini?';
      if (!window.confirm(message)) event.preventDefault();
    });
  });

  document.querySelectorAll('[data-filter-table]').forEach((input) => {
    const table = document.querySelector(input.dataset.filterTable);
    if (!table) return;
    input.addEventListener('input', () => {
      const term = input.value.toLowerCase().trim();
      table.querySelectorAll('tbody tr').forEach((row) => {
        row.hidden = term !== '' && !row.textContent.toLowerCase().includes(term);
      });
    });
  });

  document.querySelectorAll('[data-material-filter-form]').forEach((form) => {
    const subjectSelect = form.querySelector('[data-subject-filter]');
    const materialSelect = form.querySelector('[data-material-select]');
    if (!subjectSelect || !materialSelect) return;
    const filterMaterials = () => {
      const subjectId = subjectSelect.value;
      [...materialSelect.options].forEach((option) => {
        if (!option.value) return;
        option.hidden = Boolean(subjectId) && option.dataset.subject !== subjectId;
      });
      if (materialSelect.selectedOptions[0]?.hidden) materialSelect.value = '';
    };
    subjectSelect.addEventListener('change', filterMaterials);
    filterMaterials();
  });

  document.querySelectorAll('[data-select-all]').forEach((master) => {
    master.addEventListener('change', () => {
      document.querySelectorAll(master.dataset.selectAll).forEach((box) => box.checked = master.checked);
    });
  });
})();
