document.querySelectorAll('.menu-toggle').forEach((button) => {
  const target = document.getElementById(button.getAttribute('aria-controls'));
  if (!target) return;
  button.addEventListener('click', () => {
    const open = target.classList.toggle('is-open');
    button.setAttribute('aria-expanded', String(open));
  });
  target.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => {
      target.classList.remove('is-open');
      button.setAttribute('aria-expanded', 'false');
    });
  });
});
