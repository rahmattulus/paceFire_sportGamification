document.querySelectorAll('.flash').forEach((flash) => {
  window.setTimeout(() => {
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    flash.style.transition = reducedMotion ? 'none' : 'opacity 150ms ease';
    flash.style.opacity = '0';
    window.setTimeout(() => flash.remove(), reducedMotion ? 0 : 160);
  }, 5000);
});

document.querySelectorAll('.btn, .bottom-nav a, .challenge-tabs a, .route-card').forEach((item) => {
  item.addEventListener('pointerdown', () => item.classList.add('is-pressed'));
  item.addEventListener('pointerup', () => item.classList.remove('is-pressed'));
  item.addEventListener('pointerleave', () => item.classList.remove('is-pressed'));
});
