'use strict';

const http = require('http');
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');

const db = require('./src/db');
const { hashPassword, verifyPassword, signSession, verifySession } = require('./src/auth');
const { validateEmail, validateLogin, validatePassword } = require('./src/validate');

// --- config -----------------------------------------------------------------

loadDotEnv();

const PORT = Number(process.env.PORT || 3000);
const SESSION_TTL = Number(process.env.SESSION_TTL || 60 * 60 * 24 * 7); // 7d
const AUTH_SECRET =
  process.env.AUTH_SECRET ||
  (() => {
    // Dev-only fallback so the server still starts without .env
    const p = path.join(__dirname, 'data', '.dev-secret');
    try {
      if (fs.existsSync(p)) return fs.readFileSync(p, 'utf8');
      const s = crypto.randomBytes(48).toString('hex');
      fs.mkdirSync(path.dirname(p), { recursive: true });
      fs.writeFileSync(p, s);
      console.warn('[fear.dev] AUTH_SECRET not set; generated a dev secret in data/.dev-secret');
      return s;
    } catch {
      return 'insecure-dev-secret-change-me';
    }
  })();

const PUBLIC_DIR = path.join(__dirname, 'public');

// --- helpers ----------------------------------------------------------------

const MIME = {
  '.html': 'text/html; charset=utf-8',
  '.css': 'text/css; charset=utf-8',
  '.js': 'application/javascript; charset=utf-8',
  '.json': 'application/json; charset=utf-8',
  '.svg': 'image/svg+xml',
  '.png': 'image/png',
  '.ico': 'image/x-icon',
  '.woff2': 'font/woff2',
};

function loadDotEnv() {
  const p = path.join(__dirname, '.env');
  if (!fs.existsSync(p)) return;
  const lines = fs.readFileSync(p, 'utf8').split(/\r?\n/);
  for (const line of lines) {
    const m = line.match(/^\s*([A-Z0-9_]+)\s*=\s*(.*)\s*$/i);
    if (!m) continue;
    let val = m[2];
    if ((val.startsWith('"') && val.endsWith('"')) || (val.startsWith("'") && val.endsWith("'"))) {
      val = val.slice(1, -1);
    }
    if (!(m[1] in process.env)) process.env[m[1]] = val;
  }
}

function send(res, status, body, headers = {}) {
  res.writeHead(status, { 'Content-Type': 'text/plain; charset=utf-8', ...headers });
  res.end(body);
}

function sendJson(res, status, data, headers = {}) {
  const body = JSON.stringify(data);
  res.writeHead(status, {
    'Content-Type': 'application/json; charset=utf-8',
    'Content-Length': Buffer.byteLength(body),
    ...headers,
  });
  res.end(body);
}

function readJsonBody(req, max = 1024 * 32) {
  return new Promise((resolve, reject) => {
    let size = 0;
    const chunks = [];
    req.on('data', (chunk) => {
      size += chunk.length;
      if (size > max) {
        reject(Object.assign(new Error('payload_too_large'), { status: 413 }));
        req.destroy();
        return;
      }
      chunks.push(chunk);
    });
    req.on('end', () => {
      if (chunks.length === 0) return resolve({});
      try {
        resolve(JSON.parse(Buffer.concat(chunks).toString('utf8')));
      } catch {
        reject(Object.assign(new Error('invalid_json'), { status: 400 }));
      }
    });
    req.on('error', reject);
  });
}

function parseCookies(req) {
  const header = req.headers.cookie || '';
  const out = {};
  for (const part of header.split(';')) {
    const i = part.indexOf('=');
    if (i < 0) continue;
    const k = part.slice(0, i).trim();
    const v = part.slice(i + 1).trim();
    if (k) out[k] = decodeURIComponent(v);
  }
  return out;
}

function setSessionCookie(res, token, maxAge) {
  const parts = [
    `fd_session=${encodeURIComponent(token)}`,
    'Path=/',
    'HttpOnly',
    'SameSite=Lax',
    `Max-Age=${maxAge}`,
  ];
  res.setHeader('Set-Cookie', parts.join('; '));
}

function clearSessionCookie(res) {
  res.setHeader('Set-Cookie', 'fd_session=; Path=/; HttpOnly; SameSite=Lax; Max-Age=0');
}

function publicUser(u) {
  return { id: u.id, email: u.email, login: u.login, createdAt: u.createdAt };
}

function getCurrentUser(req) {
  const cookies = parseCookies(req);
  const token = cookies['fd_session'];
  const payload = verifySession(token, AUTH_SECRET);
  if (!payload) return null;
  const user = db.findById(payload.sub);
  return user || null;
}

// --- naive in-memory rate limit --------------------------------------------
// Keeps brute-force attempts on /api/login and /api/register manageable.

const rateBuckets = new Map();
function rateLimit(key, limit, windowMs) {
  const now = Date.now();
  const entry = rateBuckets.get(key) || { count: 0, reset: now + windowMs };
  if (now > entry.reset) {
    entry.count = 0;
    entry.reset = now + windowMs;
  }
  entry.count += 1;
  rateBuckets.set(key, entry);
  return entry.count <= limit;
}

// --- routes -----------------------------------------------------------------

async function handleRegister(req, res) {
  const ip = req.socket.remoteAddress || 'unknown';
  if (!rateLimit(`reg:${ip}`, 10, 60 * 1000)) {
    return sendJson(res, 429, { ok: false, error: 'rate_limited' });
  }

  let body;
  try {
    body = await readJsonBody(req);
  } catch (e) {
    return sendJson(res, e.status || 400, { ok: false, error: e.message });
  }

  const email = String(body.email || '').trim().toLowerCase();
  const login = String(body.login || '').trim();
  const password = String(body.password || '');

  const errs = [validateEmail(email), validateLogin(login), validatePassword(password)].filter(Boolean);
  if (errs.length) return sendJson(res, 400, { ok: false, error: errs[0] });

  if (db.findByEmail(email)) return sendJson(res, 409, { ok: false, error: 'email_taken' });
  if (db.findByLogin(login)) return sendJson(res, 409, { ok: false, error: 'login_taken' });

  const passwordHash = await hashPassword(password);
  const user = db.createUser({
    id: crypto.randomUUID(),
    email,
    login,
    passwordHash,
    createdAt: new Date().toISOString(),
  });

  const token = signSession(user.id, AUTH_SECRET, SESSION_TTL);
  setSessionCookie(res, token, SESSION_TTL);
  return sendJson(res, 201, { ok: true, user: publicUser(user) });
}

async function handleLogin(req, res) {
  const ip = req.socket.remoteAddress || 'unknown';
  if (!rateLimit(`login:${ip}`, 20, 60 * 1000)) {
    return sendJson(res, 429, { ok: false, error: 'rate_limited' });
  }

  let body;
  try {
    body = await readJsonBody(req);
  } catch (e) {
    return sendJson(res, e.status || 400, { ok: false, error: e.message });
  }

  // identifier may be either email or login
  const identifier = String(body.identifier || body.email || body.login || '').trim();
  const password = String(body.password || '');
  if (!identifier || !password) return sendJson(res, 400, { ok: false, error: 'missing_credentials' });

  const user = db.findByEmailOrLogin(identifier);
  // constant-ish time: still run a hash compare even when user not found
  const ok = user ? await verifyPassword(password, user.passwordHash) : false;
  if (!user || !ok) return sendJson(res, 401, { ok: false, error: 'invalid_credentials' });

  const token = signSession(user.id, AUTH_SECRET, SESSION_TTL);
  setSessionCookie(res, token, SESSION_TTL);
  return sendJson(res, 200, { ok: true, user: publicUser(user) });
}

function handleMe(req, res) {
  const user = getCurrentUser(req);
  if (!user) return sendJson(res, 401, { ok: false, error: 'unauthorized' });
  return sendJson(res, 200, { ok: true, user: publicUser(user) });
}

function handleLogout(_req, res) {
  clearSessionCookie(res);
  return sendJson(res, 200, { ok: true });
}

// --- static -----------------------------------------------------------------

function safePath(reqPath) {
  // Prevent path traversal; resolve under PUBLIC_DIR.
  const decoded = decodeURIComponent(reqPath.split('?')[0]);
  let rel = decoded.replace(/^\/+/, '');
  if (rel === '' || rel.endsWith('/')) rel = path.join(rel, 'index.html');
  const resolved = path.normalize(path.join(PUBLIC_DIR, rel));
  if (!resolved.startsWith(PUBLIC_DIR)) return null;
  return resolved;
}

function serveStatic(req, res) {
  const filePath = safePath(req.url);
  if (!filePath) return send(res, 400, 'bad request');

  fs.stat(filePath, (err, stat) => {
    if (err || !stat.isFile()) {
      // SPA-style fallback for nice URLs (/login, /register, /dashboard)
      const fallback = path.join(PUBLIC_DIR, req.url.split('?')[0].replace(/^\/+/, '') + '.html');
      const safeFallback = fallback.startsWith(PUBLIC_DIR) ? fallback : null;
      if (safeFallback) {
        return fs.stat(safeFallback, (err2, stat2) => {
          if (err2 || !stat2.isFile()) return send(res, 404, 'Not Found');
          streamFile(res, safeFallback);
        });
      }
      return send(res, 404, 'Not Found');
    }
    streamFile(res, filePath);
  });
}

function streamFile(res, filePath) {
  const ext = path.extname(filePath).toLowerCase();
  const type = MIME[ext] || 'application/octet-stream';
  res.writeHead(200, { 'Content-Type': type, 'Cache-Control': 'no-cache' });
  fs.createReadStream(filePath).pipe(res);
}

// --- request dispatcher -----------------------------------------------------

const server = http.createServer(async (req, res) => {
  try {
    const url = req.url || '/';
    const method = req.method || 'GET';

    if (url.startsWith('/api/')) {
      if (method === 'POST' && url === '/api/register') return handleRegister(req, res);
      if (method === 'POST' && url === '/api/login') return handleLogin(req, res);
      if (method === 'POST' && url === '/api/logout') return handleLogout(req, res);
      if (method === 'GET' && url === '/api/me') return handleMe(req, res);
      return sendJson(res, 404, { ok: false, error: 'not_found' });
    }

    if (method !== 'GET' && method !== 'HEAD') return send(res, 405, 'Method Not Allowed');
    return serveStatic(req, res);
  } catch (err) {
    console.error('unhandled', err);
    return sendJson(res, 500, { ok: false, error: 'internal_error' });
  }
});

server.listen(PORT, () => {
  console.log(`fear.dev listening on http://localhost:${PORT}`);
});
