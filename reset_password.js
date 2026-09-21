document.addEventListener('DOMContentLoaded', function () {

  // ---------- Show/hide password ----------
  document.querySelectorAll('.password-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const target = document.getElementById(btn.dataset.target);
      const isHidden = target.type === 'password';
      target.type = isHidden ? 'text' : 'password';
      btn.textContent = isHidden ? 'Hide' : 'Show';
    });
  });

  const form = document.getElementById('resetForm');
  if (!form) return;   // the "link not valid" screen has no form

  const passwordInput = document.getElementById('password');
  const confirmInput  = document.getElementById('confirm');

  const order = ['password', 'confirm'];

  const fields = {
    password: { input: passwordInput, wrap: document.getElementById('field-password'), touched: false },
    confirm:  { input: confirmInput,  wrap: document.getElementById('field-confirm'),  touched: false }
  };

  function setState(fieldName, isValid, message, forceShow) {
    const f = fields[fieldName];
    const msgBox = f.wrap.querySelector('.field-msg');

    if (!f.touched && !forceShow) {
      f.wrap.classList.remove('valid', 'invalid');
      msgBox.textContent = '';
      return;
    }

    f.wrap.classList.remove('valid', 'invalid');
    f.wrap.classList.add(isValid ? 'valid' : 'invalid');
    msgBox.textContent = isValid ? '' : message;
  }

  // ---------- New password (same rules as the register page) ----------
  function validatePassword(showError) {
    const value = passwordInput.value;
    if (value === '') {
      if (showError) setState('password', false, 'Password is required.');
      else setState('password', true, '');
      return false;
    }
    if (value.length < 8) {
      setState('password', false, 'Password must be at least 8 characters.', showError);
      return false;
    }
    if (!/[A-Za-z]/.test(value)) {
      setState('password', false, 'Password must contain at least one letter.', true);
      return false;
    }
    if (!/\d/.test(value)) {
      setState('password', false, 'Password must contain at least one number.', true);
      return false;
    }
    if (!/[^A-Za-z0-9]/.test(value)) {
      setState('password', false, 'Password must contain at least one symbol (e.g. ! @ # $).', true);
      return false;
    }
    setState('password', true, '');
    return true;
  }

  // ---------- Confirm password ----------
  function validateConfirm(showError) {
    const value = confirmInput.value;
    if (value === '') {
      if (showError) setState('confirm', false, 'Please confirm your password.');
      else setState('confirm', true, '');
      return false;
    }
    const ok = value === passwordInput.value;
    setState('confirm', ok, 'The two passwords do not match.', true);
    return ok;
  }

  passwordInput.addEventListener('input', function () {
    validatePassword(fields.password.touched);
    // keep the confirm box honest if the first password changes
    if (fields.confirm.touched) validateConfirm(true);
  });
  passwordInput.addEventListener('blur', function () { fields.password.touched = true; validatePassword(true); });

  confirmInput.addEventListener('input', function () { validateConfirm(fields.confirm.touched); });
  confirmInput.addEventListener('blur', function () { fields.confirm.touched = true; validateConfirm(true); });

  // ---------- Enter key moves to the next field ----------
  const validators = {
    password: validatePassword,
    confirm: validateConfirm
  };

  order.forEach(function (name, idx) {
    fields[name].input.addEventListener('keydown', function (e) {
      if (e.key !== 'Enter') return;
      e.preventDefault();
      fields[name].touched = true;
      const isValid = validators[name](true);
      if (!isValid) return;

      const nextName = order[idx + 1];
      if (nextName) {
        fields[nextName].input.focus();
      } else {
        form.submit();
      }
    });
  });

  // ---------- Final check on submit ----------
  form.addEventListener('submit', function (e) {
    let allValid = true;
    order.forEach(function (name) {
      fields[name].touched = true;
      if (!validators[name](true)) allValid = false;
    });
    if (!allValid) e.preventDefault();
  });

});
