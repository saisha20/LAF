/**
 * register.js
 * Client-side validation for the registration form.
 * These rules MIRROR validate.php exactly, so the user never sees
 * the JS say "valid" and the server say "invalid" or vice versa.
 * NOTE: This is UX only - validate.php on the server is the real
 * security layer and must never be trusted to JS alone.
 */

document.addEventListener('DOMContentLoaded', function () {

  const nameInput     = document.getElementById('full_name');
  const emailInput    = document.getElementById('email');
  const phoneInput    = document.getElementById('phone');
  const passwordInput = document.getElementById('password');
  const form           = document.getElementById('registerForm');

  const order = ['full_name', 'email', 'phone', 'password'];

  const fields = {
    full_name: { input: nameInput,     wrap: document.getElementById('field-full_name'), touched: false },
    email:     { input: emailInput,    wrap: document.getElementById('field-email'),     touched: false },
    phone:     { input: phoneInput,    wrap: document.getElementById('field-phone'),     touched: false },
    password:  { input: passwordInput, wrap: document.getElementById('field-password'),  touched: false }
  };

  function setState(fieldName, isValid, message, forceShow) {
    const f = fields[fieldName];
    const msgBox = f.wrap.querySelector('.field-msg');

    // Don't scare the user with red before they've left the field
    // or typed something invalid mid-way (e.g. just "S").
    if (!f.touched && !forceShow) {
      f.wrap.classList.remove('valid', 'invalid');
      msgBox.textContent = '';
      return;
    }

    f.wrap.classList.remove('valid', 'invalid');
    f.wrap.classList.add(isValid ? 'valid' : 'invalid');
    msgBox.textContent = isValid ? '' : message;
  }

  // ---------- Full Name ----------
  // Letters and single spaces only, cannot start with a number/symbol
  function validateName(showError) {
    const value = nameInput.value.trim();
    if (value === '') {
      if (showError) setState('full_name', false, 'Full name is required.');
      else setState('full_name', true, '');
      return false;
    }
    const ok = /^[A-Za-z]+( [A-Za-z]+)*$/.test(value) && value.length >= 2;
    setState('full_name', ok, 'Only letters and spaces are allowed.', true);
    return ok;
  }

  // While actively typing, only flag a genuinely bad character
  // (a number or symbol) - don't punish a normal partial name.
  nameInput.addEventListener('input', function () {
    const value = nameInput.value;
    if (value === '') {
      setState('full_name', true, '');
      return;
    }
    const hasBadChar = /[^A-Za-z ]/.test(value) || /^\s/.test(value);
    if (hasBadChar) {
      setState('full_name', false, 'Only letters and spaces are allowed.', true);
    } else {
      setState('full_name', true, '');
    }
  });
  nameInput.addEventListener('blur', function () { fields.full_name.touched = true; validateName(true); });

  // ---------- University Email ----------
  function validateEmail(showError) {
    const value = emailInput.value.trim();
    if (value === '') {
      if (showError) setState('email', false, 'Email is required.');
      else setState('email', true, '');
      return false;
    }
    const shapeOk = /^[A-Za-z][A-Za-z0-9._%+-]*@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/.test(value);
    if (!shapeOk) {
      const startsWithDigit = /^\d/.test(value);
      setState('email', false, startsWithDigit ? 'Email cannot start with a number.' : 'Enter a valid email address.', showError);
      return false;
    }
    const domainOk = /@kathford\.edu\.np$/i.test(value);
    setState('email', domainOk, 'Please use your college email ending with @kathford.edu.np.', true);
    return domainOk;
  }

  emailInput.addEventListener('input', function () {
    // Don't flare up an error while they're still mid-way through
    // typing (e.g. "saisha@") - only block clearly invalid characters.
    const value = emailInput.value;
    const hasInvalidChar = /[^A-Za-z0-9._%+\-@]/.test(value);
    const startsWithDigit = /^\d/.test(value);
    if (startsWithDigit) {
      setState('email', false, 'Email cannot start with a number.', true);
    } else if (hasInvalidChar) {
      setState('email', false, 'That character is not allowed in an email.', true);
    } else if (fields.email.touched) {
      validateEmail(true);
    } else {
      setState('email', true, '');
    }
  });
  emailInput.addEventListener('blur', function () { fields.email.touched = true; validateEmail(true); });

  // ---------- Phone Number ----------
  function validatePhone(showError) {
    const value = phoneInput.value.trim();
    if (value === '') {
      if (showError) setState('phone', false, 'Phone number is required.');
      else setState('phone', true, '');
      return false;
    }
    if (!/^\d+$/.test(value)) {
      setState('phone', false, 'Phone number must contain digits only.', true);
      return false;
    }
    if (value.length !== 10) {
      setState('phone', false, 'Phone number must contain exactly 10 digits.', showError);
      return false;
    }
    const ok = /^(97|98)\d{8}$/.test(value);
    setState('phone', ok, 'Phone number must start with 97 or 98.', true);
    return ok;
  }

    phoneInput.addEventListener('input', function () {
    // Strip any non-digit character as they type
    phoneInput.value = phoneInput.value.replace(/\D/g, '').slice(0, 10);
    const value = phoneInput.value;

    if (value.length === 0) {
      setState('phone', true, '');
      return;
    }

    // As soon as what's typed so far can no longer become "97" or "98",
    // flag it right away - even after just the first digit.
    const prefixSoFar = value.slice(0, 2);
    const stillPossible = '97'.startsWith(prefixSoFar) || '98'.startsWith(prefixSoFar);

    if (!stillPossible) {
      setState('phone', false, 'Phone number must start with 97 or 98.', true);
    } else if (value.length === 10 || fields.phone.touched) {
      validatePhone(true);
    } else {
      setState('phone', true, '');
    }
  });
  
  phoneInput.addEventListener('blur', function () { fields.phone.touched = true; validatePhone(true); });

  // ---------- Password ----------
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

  passwordInput.addEventListener('input', function () { validatePassword(fields.password.touched); });
  passwordInput.addEventListener('blur', function () { fields.password.touched = true; validatePassword(true); });

  // ---------- Enter key moves to the next field ----------
  const validators = {
    full_name: validateName,
    email: validateEmail,
    phone: validatePhone,
    password: validatePassword
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

  // ---------- Show/hide password ----------
  document.querySelectorAll('.password-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const target = document.getElementById(btn.dataset.target);
      const isHidden = target.type === 'password';
      target.type = isHidden ? 'text' : 'password';
      btn.textContent = isHidden ? 'Hide' : 'Show';
    });
  });

});
