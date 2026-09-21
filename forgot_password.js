document.addEventListener('DOMContentLoaded', function () {

  const form       = document.getElementById('forgotForm');
  if (!form) return;   // the "check your email" screen has no form

  const emailInput = document.getElementById('email');
  const fields = {
    email: { input: emailInput, wrap: document.getElementById('field-email'), touched: false }
  };

  function setState(fieldName, isValid, message, forceShow) {
    const f = fields[fieldName];
    const msgBox = f.wrap.querySelector('.field-msg');

    // Don't scare the user with red before they've left the field
    // or typed something invalid mid-way.
    if (!f.touched && !forceShow) {
      f.wrap.classList.remove('valid', 'invalid');
      msgBox.textContent = '';
      return;
    }

    f.wrap.classList.remove('valid', 'invalid');
    f.wrap.classList.add(isValid ? 'valid' : 'invalid');
    msgBox.textContent = isValid ? '' : message;
  }

  // ---------- University Email (same rules as the register page) ----------
  const COLLEGE_DOMAIN = 'kathford.edu.np';
  const DOMAIN_MSG = 'Please use your college email ending with @kathford.edu.np.';

  // Text typed after the @ (lower-case), or null if there is no @ yet.
  function domainSoFar(value) {
    const at = value.indexOf('@');
    return at === -1 ? null : value.slice(at + 1).toLowerCase();
  }

  // true while what is typed after the @ can still grow into kathford.edu.np
  function domainCanStillMatch(value) {
    const d = domainSoFar(value);
    return d === null || COLLEGE_DOMAIN.startsWith(d);
  }

  function validateEmail(showError) {
    const value = emailInput.value.trim();
    if (value === '') {
      if (showError) setState('email', false, 'Email is required.');
      else setState('email', true, '');
      return false;
    }
    if (/^\d/.test(value)) {
      setState('email', false, 'Email cannot start with a number.', showError);
      return false;
    }
    // A wrong domain such as @gmail.com gets the college-email message,
    // even when the address is not finished yet.
    if (value.split('@').length === 2 && !domainCanStillMatch(value)) {
      setState('email', false, DOMAIN_MSG, true);
      return false;
    }
    const shapeOk = /^[A-Za-z][A-Za-z0-9._%+-]*@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/.test(value);
    if (!shapeOk) {
      setState('email', false, 'Enter a valid email address.', showError);
      return false;
    }
    const domainOk = /@kathford\.edu\.np$/i.test(value);
    setState('email', domainOk, DOMAIN_MSG, true);
    return domainOk;
  }

  emailInput.addEventListener('input', function () {
    // Stay quiet while the address is still on its way to being valid
    // (e.g. "saisha@" or "saisha@kath"). Flag it the moment it cannot
    // become a valid college email any more.
    const value = emailInput.value;
    const hasInvalidChar = /[^A-Za-z0-9._%+\-@]/.test(value);
    const startsWithDigit = /^\d/.test(value);
    const atCount = value.split('@').length - 1;

    if (startsWithDigit) {
      setState('email', false, 'Email cannot start with a number.', true);
    } else if (hasInvalidChar) {
      setState('email', false, 'That character is not allowed in an email.', true);
    } else if (atCount > 1) {
      setState('email', false, 'Enter a valid email address.', true);
    } else if (!domainCanStillMatch(value)) {
      setState('email', false, DOMAIN_MSG, true);
    } else if (fields.email.touched) {
      validateEmail(true);
    } else {
      setState('email', true, '');
    }
  });
  emailInput.addEventListener('blur', function () { fields.email.touched = true; validateEmail(true); });

  // ---------- Enter key submits when the email is valid ----------
  emailInput.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter') return;
    e.preventDefault();
    fields.email.touched = true;
    if (validateEmail(true)) form.submit();
  });

  // ---------- Final check on submit ----------
  form.addEventListener('submit', function (e) {
    fields.email.touched = true;
    if (!validateEmail(true)) e.preventDefault();
  });

});
