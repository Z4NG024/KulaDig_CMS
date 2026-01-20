/* Einfacher Hero-Slider: mehrere Wrapper, manuelle Pfeile, Auto-Advance. */
document.addEventListener('DOMContentLoaded', function () {
  const wrappers = document.querySelectorAll('.kuladig-hero-wrapper');

  wrappers.forEach(function (wrapper) {
    const slides = wrapper.querySelectorAll('.kuladig-hero-slide');
    if (!slides.length) return;

    let current = 0;
    slides[current].classList.add('is-active');

    const prevBtn = wrapper.querySelector('.kuladig-hero-prev');
    const nextBtn = wrapper.querySelector('.kuladig-hero-next');

    function showSlide(index) {
      slides[current].classList.remove('is-active');
      current = (index + slides.length) % slides.length;
      slides[current].classList.add('is-active');
    }

    // Pfeile nur binden, wenn sie im Markup vorhanden sind.
    if (prevBtn) {
      prevBtn.addEventListener('click', function () {
        showSlide(current - 1);
      });
    }

    if (nextBtn) {
      nextBtn.addEventListener('click', function () {
        showSlide(current + 1);
      });
    }

    // Auto-Slide (kurz), bei Hover pausieren.
    let timer = setInterval(function () {
      showSlide(current + 1);
    }, 4000);

    wrapper.addEventListener('mouseenter', function () {
      clearInterval(timer);
    });
    wrapper.addEventListener('mouseleave', function () {
      timer = setInterval(function () {
        showSlide(current + 1);
      }, 8000);
    });
  });
});
