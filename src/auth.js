const express = require('express');
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const db = require('./db');
const { validateRegister, validateLogin } = require('./validate');

const router = express.Router();

const JWT_SECRET = process.env.JWT_SECRET || 'dev-secret-change-me';
const COOKIE_NAME = 'lb_token';
const COOKIE_OPTS = {
  httpOnly: true,
  sameSite: 'lax',
  secure: process.env.NODE_ENV === 'production',
  maxAge: 7 * 24 * 60 * 60 * 1000,
};

function signToken(user) {
  return jwt.sign({ id: user.id, login: user.login }, JWT_SECRET, { expiresIn: '7d' });
}

function authRequired(req, res, next) {
  const token = req.cookies?.[COOKIE_NAME];
  if (!token) return res.status(401).json({ error: 'unauthorized' });
  try {
    req.user = jwt.verify(token, JWT_SECRET);
    next();
  } catch {
    return res.status(401).json({ error: 'unauthorized' });
  }
}

router.post('/register', async (req, res) => {
  const { login, email, password } = req.body || {};
  const { ok, errors } = validateRegister({ login, email, password });
  if (!ok) return res.status(400).json({ error: 'validation', fields: errors });

  const exists = db
    .prepare('SELECT 1 FROM users WHERE login = ? OR email = ?')
    .get(login, email);
  if (exists) {
    return res.status(409).json({ error: 'exists', message: 'Логин или e-mail уже заняты.' });
  }

  const hash = await bcrypt.hash(password, 10);
  const info = db
    .prepare('INSERT INTO users (login, email, password_hash, created_at) VALUES (?, ?, ?, ?)')
    .run(login, email, hash, Date.now());

  const user = { id: info.lastInsertRowid, login, email };
  res.cookie(COOKIE_NAME, signToken(user), COOKIE_OPTS);
  res.json({ ok: true, user: { id: user.id, login: user.login, email: user.email } });
});

router.post('/login', async (req, res) => {
  const { identifier, password } = req.body || {};
  const { ok, errors } = validateLogin({ identifier, password });
  if (!ok) return res.status(400).json({ error: 'validation', fields: errors });

  const row = db
    .prepare('SELECT id, login, email, password_hash FROM users WHERE login = ? OR email = ?')
    .get(identifier, identifier);
  if (!row) return res.status(401).json({ error: 'invalid', message: 'Неверный логин или пароль.' });

  const match = await bcrypt.compare(password, row.password_hash);
  if (!match) return res.status(401).json({ error: 'invalid', message: 'Неверный логин или пароль.' });

  res.cookie(COOKIE_NAME, signToken(row), COOKIE_OPTS);
  res.json({ ok: true, user: { id: row.id, login: row.login, email: row.email } });
});

router.post('/logout', (_req, res) => {
  res.clearCookie(COOKIE_NAME, { ...COOKIE_OPTS, maxAge: 0 });
  res.json({ ok: true });
});

router.get('/me', authRequired, (req, res) => {
  const row = db
    .prepare('SELECT id, login, email, created_at FROM users WHERE id = ?')
    .get(req.user.id);
  if (!row) return res.status(401).json({ error: 'unauthorized' });
  res.json({ user: row });
});

module.exports = { router, authRequired };
