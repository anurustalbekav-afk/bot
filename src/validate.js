'use strict';

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/i;
const LOGIN_RE = /^[A-Za-z0-9_]{3,24}$/;

function validateEmail(email) {
  if (typeof email !== 'string') return 'invalid_email';
  const v = email.trim();
  if (v.length === 0) return 'email_required';
  if (v.length > 254) return 'email_too_long';
  if (!EMAIL_RE.test(v)) return 'invalid_email';
  return null;
}

function validateLogin(login) {
  if (typeof login !== 'string') return 'invalid_login';
  const v = login.trim();
  if (v.length === 0) return 'login_required';
  if (!LOGIN_RE.test(v)) return 'invalid_login';
  return null;
}

function validatePassword(password) {
  if (typeof password !== 'string') return 'invalid_password';
  if (password.length < 8) return 'password_too_short';
  if (password.length > 128) return 'password_too_long';
  return null;
}

module.exports = { validateEmail, validateLogin, validatePassword };
