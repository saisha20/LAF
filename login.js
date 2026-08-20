document.addEventListener('DOMContentLoaded', function () {

  const emailInput    = document.getElementById('email');
  const passwordInput = document.getElementById('password');
  const form           = document.getElementById('loginForm');

  const fields = {
    email:    { input: emailInput,    wrap: document.getElementById('field-email'),    touched: false },
    password: { input: passwordInput, wrap: document.getElementById('field-password'), touched: false }
  };

  function setState(fieldName, isValid, message) {
    const f = fields[fieldName];
    const msgBox = f.wrap.querySelector('.field-msg');

    if (!f.touched) {
      
      f.wrap.classList.remove('valid', 'invalid');
      msgBox.textContent = '';
      return;
    }

    f.wrap.classList.remove('valid', 'invalid');
    f.wrap.classList.add(isValid ? 'valid' : 'invalid');
    msgBox.textContent = isValid ? '' : message;
  }

  function validateEmail(showError) {
    const value = emailInput.value.trim();
    if (value === '') {
      if (showError) setState('email', false, 'Email is required.');
      return false;
    }
    const ok = /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/.test(value);
    setState('email', ok, 'Enter a valid email address.');
    return ok;
  }

  function validatePassword(showError) {
    const value = passwordInput.value;
    if (value === '') {
      if (showError) setState('password', false, 'Password is required.');
      return false;
    }
    setState('password', true, '');
    return true;
  }

  emailInput.addEventListener('input', () => validateEmail(true));
  passwordInput.addEventListener('input', () => validatePassword(true));

  emailInput.addEventListener('blur', () => { fields.email.touched = true; validateEmail(true); });
  passwordInput.addEventListener('blur', () => { fields.password.touched = true; validatePassword(true); });

  emailInput.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      fields.email.touched = true;
      if (validateEmail(true)) {
        passwordInput.focus();
      }
    }
  });

  passwordInput.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      fields.password.touched = true;
      if (validatePassword(true)) {
        form.submit();
      }
    }
  });

  form.addEventListener('submit', function (e) {
    fields.email.touched = true;
    fields.password.touched = true;
    const emailOk = validateEmail(true);
    const passOk  = validatePassword(true);
    if (!emailOk || !passOk) {
      e.preventDefault();
    }
  });

  document.querySelectorAll('.password-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const target = document.getElementById(btn.dataset.target);
      const isHidden = target.type === 'password';
      target.type = isHidden ? 'text' : 'password';
      btn.textContent = isHidden ? 'Hide' : 'Show';
    });
  });

});