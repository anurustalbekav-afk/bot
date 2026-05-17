const Database = require('better-sqlite3');
const path = require('path');
const fs = require('fs');

const dataDir = path.join(__dirname, '..', 'data');
if (!fs.existsSync(dataDir)) fs.mkdirSync(dataDir, { recursive: true });

const db = new Database(path.join(dataDir, 'app.sqlite'));
db.pragma('journal_mode = WAL');

db.exec(`
  CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    login TEXT UNIQUE NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    created_at INTEGER NOT NULL
  );

  CREATE TABLE IF NOT EXISTS news (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tag TEXT NOT NULL,
    title TEXT NOT NULL,
    body TEXT NOT NULL,
    published_at INTEGER NOT NULL
  );

  CREATE TABLE IF NOT EXISTS servers (
    id INTEGER PRIMARY KEY,
    name TEXT NOT NULL,
    online INTEGER NOT NULL DEFAULT 0,
    capacity INTEGER NOT NULL DEFAULT 1000,
    ping INTEGER NOT NULL DEFAULT 0
  );
`);

// Seed news once
const newsCount = db.prepare('SELECT COUNT(*) AS c FROM news').get().c;
if (newsCount === 0) {
  const insert = db.prepare(
    'INSERT INTO news (tag, title, body, published_at) VALUES (?, ?, ?, ?)'
  );
  const now = Date.now();
  insert.run('important', 'Открытие проекта', 'Сервер открылся! Ждём каждого из наших игроков.', now - 3 * 86400000);
  insert.run('update', 'Стабильные обновления', 'Мы регулярно выпускаем обновления для вашего комфорта.', now - 4 * 86400000);
  insert.run('event', 'Ивенты и мероприятия', 'Участвуй в мероприятиях и получай уникальные награды!', now - 5 * 86400000);
}

// Seed servers once
const srvCount = db.prepare('SELECT COUNT(*) AS c FROM servers').get().c;
if (srvCount === 0) {
  const insert = db.prepare('INSERT INTO servers (id, name, online, capacity, ping) VALUES (?, ?, ?, ?, ?)');
  insert.run(1, 'LAMBADA BONUS | 01 SERVER', 856, 1000, 65);
  insert.run(2, 'LAMBADA BONUS | 02 SERVER', 623, 1000, 78);
  insert.run(3, 'LAMBADA BONUS | 03 SERVER', 412, 1000, 102);
}

module.exports = db;
