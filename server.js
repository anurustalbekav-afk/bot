require('dotenv').config();
const express = require('express');
const path = require('path');
const cookieParser = require('cookie-parser');
const db = require('./src/db');
const { router: authRouter } = require('./src/auth');

const app = express();
const PORT = Number(process.env.PORT) || 3000;

app.use(express.json({ limit: '64kb' }));
app.use(cookieParser());

// API
app.use('/api', authRouter);

app.get('/api/servers', (_req, res) => {
  const rows = db.prepare('SELECT id, name, online, capacity, ping FROM servers ORDER BY id').all();
  res.json({ servers: rows, totalOnline: rows.reduce((s, r) => s + r.online, 0) });
});

app.get('/api/news', (_req, res) => {
  const rows = db
    .prepare('SELECT id, tag, title, body, published_at FROM news ORDER BY published_at DESC LIMIT 10')
    .all();
  res.json({ news: rows });
});

// Static
app.use(express.static(path.join(__dirname, 'public'), { extensions: ['html'] }));

// SPA-ish fallback for unknown routes -> index
app.use((req, res, next) => {
  if (req.method !== 'GET') return next();
  if (req.path.startsWith('/api/')) return next();
  res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

app.listen(PORT, () => {
  console.log(`LAMBADA BONUS site running on http://localhost:${PORT}`);
});
