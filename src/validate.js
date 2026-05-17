const LOGIN_RE = /^[A-Za-z0-9_]{3,20}$/;
const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

function validateRegister({ login, email, password }) {
  const errors = {};
  if (!login || !LOGIN_RE.test(login)) {
    errors.login = 'Логин: 3–20 символов, латиница/цифры/нижнее подчёркивание.';
  }
  if (!email || !EMAIL_RE.test(email) || email.length > 120) {
    errors.email = 'Введите корректный e-mail.';
  }
  if (!password || password.length < 6 || password.length > 100) {
    errors.password = 'Пароль: от 6 до 100 символов.';
  }
  return { ok: Object.keys(errors).length === 0, errors };
}

function validateLogin({ identifier, password }) {
  const errors = {};
  if (!identifier || identifier.length < 3 || identifier.length > 120) {
    errors.identifier = 'Введите логин или e-mail.';
  }
  if (!password || password.length < 6) {
    errors.password = 'Введите пароль.';
  }
  return { ok: Object.keys(errors).length === 0, errors };
}

module.exports = { validateRegister, validateLogin };
