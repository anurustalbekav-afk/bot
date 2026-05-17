'use strict';

const mysql = require('mysql2/promise');

/**
 * MySQL / MariaDB слой доступа к данным.
 *
 * Конфигурация — через переменные окружения (см. .env.example):
 *   DB_HOST, DB_PORT, DB_USER, DB_PASSWORD, DB_NAME, DB_CONNECTION_LIMIT
 *
 * API совместим со старым JSON-хранилищем, но методы теперь async:
 *   findByEmail, findByLogin, findByEmailOrLogin, findById, createUser,
 *   init, getPool.
 */

let pool = null;

function getPool() {
  if (pool) return pool;
  pool = mysql.createPool({
    host: process.env.DB_HOST || '127.0.0.1',
    port: Number(process.env.DB_PORT || 3306),
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASSWORD || '',
    database: process.env.DB_NAME || 'fear_dev',
    connectionLimit: Number(process.env.DB_CONNECTION_LIMIT || 10),
    waitForConnections: true,
    charset: 'utf8mb4_unicode_ci',
    dateStrings: false,
    namedPlaceholders: false,
    timezone: 'Z',
  });
  return pool;
}

/**
 * Создаёт таблицу `users`, если её ещё нет. Удобно при первом запуске,
 * чтобы не приходилось вручную импортировать схему. Идемпотентно.
 */
async function init() {
  const p = getPool();
  await p.query(`
    CREATE TABLE IF NOT EXISTS users (
      id            CHAR(36)      NOT NULL,
      email         VARCHAR(254)  NOT NULL,
      login         VARCHAR(24)   NOT NULL,
      password_hash VARCHAR(255)  NOT NULL,
      created_at    DATETIME(3)   NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
      PRIMARY KEY (id),
      UNIQUE KEY uk_users_email (email),
      UNIQUE KEY uk_users_login (login)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  `);
}

function rowToUser(row) {
  if (!row) return null;
  return {
    id: row.id,
    email: row.email,
    login: row.login,
    passwordHash: row.password_hash,
    createdAt:
      row.created_at instanceof Date ? row.created_at.toISOString() : String(row.created_at),
  };
}

async function findByEmail(email) {
  const norm = String(email || '').trim().toLowerCase();
  if (!norm) return null;
  const [rows] = await getPool().query('SELECT * FROM users WHERE email = ? LIMIT 1', [norm]);
  return rowToUser(rows[0]);
}

async function findByLogin(login) {
  const norm = String(login || '').trim();
  if (!norm) return null;
  // login сравниваем регистронезависимо — utf8mb4_unicode_ci уже это делает,
  // но на всякий случай нормализуем явно через LOWER.
  const [rows] = await getPool().query(
    'SELECT * FROM users WHERE LOWER(login) = LOWER(?) LIMIT 1',
    [norm],
  );
  return rowToUser(rows[0]);
}

async function findByEmailOrLogin(identifier) {
  const norm = String(identifier || '').trim();
  if (!norm) return null;
  const [rows] = await getPool().query(
    'SELECT * FROM users WHERE email = LOWER(?) OR LOWER(login) = LOWER(?) LIMIT 1',
    [norm, norm],
  );
  return rowToUser(rows[0]);
}

async function findById(id) {
  if (!id) return null;
  const [rows] = await getPool().query('SELECT * FROM users WHERE id = ? LIMIT 1', [id]);
  return rowToUser(rows[0]);
}

async function createUser(user) {
  // user: { id, email, login, passwordHash, createdAt }
  await getPool().query(
    `INSERT INTO users (id, email, login, password_hash, created_at)
     VALUES (?, ?, ?, ?, ?)`,
    [
      user.id,
      String(user.email).toLowerCase(),
      user.login,
      user.passwordHash,
      new Date(user.createdAt || Date.now()),
    ],
  );
  return findById(user.id);
}

async function close() {
  if (pool) {
    await pool.end();
    pool = null;
  }
}

module.exports = {
  getPool,
  init,
  close,
  findByEmail,
  findByLogin,
  findByEmailOrLogin,
  findById,
  createUser,
};
