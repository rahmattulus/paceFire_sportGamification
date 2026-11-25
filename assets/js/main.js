document.querySelectorAll('.flash').forEach((flash) => {
  window.setTimeout(() => {
    flash.style.transition = 'opacity 150ms ease';
    flash.style.opacity = '0';
  }, 4000);
});

document.querySelectorAll('.btn, .bottom-nav a, .challenge-tabs a, .route-card').forEach((item) => {
  item.addEventListener('pointerdown', () => item.classList.add('is-pressed'));
  item.addEventListener('pointerup', () => item.classList.remove('is-pressed'));
  item.addEventListener('pointerleave', () => item.classList.remove('is-pressed'));
});
