document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (event) {
      if (!confirm(el.dataset.confirm || 'Are you sure?')) event.preventDefault();
    });
  });

  const drop = document.querySelector('[data-drop-zone]');
  const input = document.querySelector('[data-file-input]');
  if (drop && input) {
    drop.addEventListener('click', () => input.click());
    ['dragenter','dragover'].forEach(evt => drop.addEventListener(evt, e => { e.preventDefault(); drop.classList.add('border-primary'); }));
    ['dragleave','drop'].forEach(evt => drop.addEventListener(evt, e => { e.preventDefault(); drop.classList.remove('border-primary'); }));
    drop.addEventListener('drop', e => { input.files = e.dataTransfer.files; drop.querySelector('strong').textContent = input.files.length + ' file(s) selected'; });
    input.addEventListener('change', () => { if(input.files.length) drop.querySelector('strong').textContent = input.files.length + ' file(s) selected'; });
  }
});
