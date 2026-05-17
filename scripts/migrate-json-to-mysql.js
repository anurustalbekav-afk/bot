'use strict';

/**
 * Одноразовая миграция пользователей из старого JSON-хранилища
 * (data/users.json) в MySQL. Идемпотентна: пропускает записи,
 * у которых email или login уже существуют в БД.
 *
 * Запуск:
 *   node scripts/migrate-json-to-mysql.js
 */

const fs = require('fs');
const path = require('path');

// Подгружаем .env вручную (server.js это тоже делает; здесь — копия)
(function loadDotEnv() {
  const p = path.join(__dirname, '..', '.env');
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
})();

const db = require('../src/db');

async function main() {
  const file = path.join(__dirname, '..', 'data', 'users.json');
  if (!fs.existsSync(file)) {
    console.log('Нет файла data/users.json — мигрировать нечего.');
    return;
  }
  const raw = JSON.parse(fs.readFileSync(file, 'utf8'));
  const users = Array.isArray(raw && raw.users) ? raw.users : [];
  if (users.length === 0) {
    console.log('Файл users.json пуст — мигрировать нечего.');
    return;
  }

  await db.init();

  let inserted = 0;
  let skipped = 0;
  for (const u of users) {
    if (!u || !u.id || !u.email || !u.login || !u.passwordHash) {
      skipped++;
      continue;
    }
    if (await db.findByEmail(u.email)) {
      skipped++;
      continue;
    }
    if (await db.findByLogin(u.login)) {
      skipped++;
      continue;
    }
    try {
      await db.createUser({
        id: u.id,
        email: u.email,
        login: u.login,
        passwordHash: u.passwordHash,
        createdAt: u.createdAt || new Date().toISOString(),
      });
      inserted++;
    } catch (err) {
      console.error(`Не удалось перенести ${u.login}:`, err.message);
      skipped++;
    }
  }

  console.log(`Готово. Перенесено: ${inserted}, пропущено: ${skipped}`);
  await db.close();
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
