document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-thumb]').forEach(function (thumb) {
    thumb.addEventListener('click', function () {
      const main = document.querySelector('[data-gallery-main]');
      if (main) main.src = thumb.getAttribute('src');
      document.querySelectorAll('[data-thumb]').forEach(t => t.classList.remove('active'));
      thumb.classList.add('active');
    });
  });

  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (event) {
      if (!confirm(el.getAttribute('data-confirm') || 'Are you sure?')) event.preventDefault();
    });
  });

  document.querySelectorAll('[data-logo-scroll]').forEach(function (button) {
    button.addEventListener('click', function () {
      const carousel = button.closest('.company-logo-carousel');
      const viewport = carousel ? carousel.querySelector('.company-logo-viewport') : null;
      if (viewport) {
        viewport.scrollBy({
          left: Number(button.getAttribute('data-logo-scroll')) * viewport.clientWidth * 0.75,
          behavior: 'smooth'
        });
      }
    });
  });
});
