(function () {
  'use strict';

  function getCredentials() {
    var fallback = { login: 'demo@demo.com', password: 'demodemo' };
    if (!window.setDemoLoginCredentials) {
      return fallback;
    }

    return {
      login: String(window.setDemoLoginCredentials.login || fallback.login),
      password: String(window.setDemoLoginCredentials.password || fallback.password)
    };
  }

  function findLoginInput(form) {
    return (
      form.querySelector('input[type="email"]') ||
      form.querySelector('input[name="email"]') ||
      form.querySelector('input[name*="email"]') ||
      form.querySelector('input[id*="email"]') ||
      null
    );
  }

  function findPasswordInput(form) {
    return (
      form.querySelector('input[type="password"]') ||
      form.querySelector('input[name="password"]') ||
      form.querySelector('input[name*="password"]') ||
      form.querySelector('input[id*="password"]') ||
      null
    );
  }

  function fillLoginForm() {
    var credentials = getCredentials();
    var form = document.querySelector('form[action*="admin_login"]') || document.querySelector('#login form') || document.querySelector('form');
    if (!form) {
      return;
    }

    var loginInput = findLoginInput(form);
    var passwordInput = findPasswordInput(form);

    if (loginInput) {
      loginInput.value = credentials.login;
      loginInput.dispatchEvent(new Event('input', { bubbles: true }));
      loginInput.dispatchEvent(new Event('change', { bubbles: true }));
    }

    if (passwordInput) {
      passwordInput.value = credentials.password;
      passwordInput.dispatchEvent(new Event('input', { bubbles: true }));
      passwordInput.dispatchEvent(new Event('change', { bubbles: true }));
    }
  }

  function bindFillButton() {
    var button = document.querySelector('[data-set-demo-fill]');
    if (!button) {
      return;
    }

    button.addEventListener('click', function () {
      fillLoginForm();
    });
  }

  function init() {
    bindFillButton();
    fillLoginForm();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
