document.querySelectorAll('.flash').forEach((flash) => {
  window.setTimeout(() => {
    flash.style.transition = 'opacity 150ms ease';
    flash.style.opacity = '0';
  }, 4000);
});
