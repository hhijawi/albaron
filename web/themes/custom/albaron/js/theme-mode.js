(() => {
  'use strict';

  const root = document.documentElement;
  const preference = window.matchMedia('(prefers-color-scheme: dark)');
  const storageKey = 'albaron-theme';
  let savedTheme = null;

  const validTheme = (value) => value === 'light' || value === 'dark';
  try {
    const stored = localStorage.getItem(storageKey);
    savedTheme = validTheme(stored) ? stored : null;
  } catch {}

  const sync = () => {
    root.dataset.theme = savedTheme || (preference.matches ? 'dark' : 'light');
    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
      const label = root.dataset.theme === 'dark' ? button.dataset.lightLabel : button.dataset.darkLabel;
      button.setAttribute('aria-label', label);
      button.title = label;
      button.hidden = false;
    });
  };

  sync();
  document.addEventListener('DOMContentLoaded', sync);
  preference.addEventListener('change', sync);
  window.addEventListener('storage', (event) => {
    if (event.key === storageKey || event.key === null) {
      savedTheme = validTheme(event.newValue) ? event.newValue : null;
      sync();
    }
  });
  document.addEventListener('click', (event) => {
    if (!(event.target instanceof Element) || !event.target.closest('[data-theme-toggle]')) {
      return;
    }
    savedTheme = root.dataset.theme === 'dark' ? 'light' : 'dark';
    try {
      localStorage.setItem(storageKey, savedTheme);
    } catch {}
    sync();
  });
})();