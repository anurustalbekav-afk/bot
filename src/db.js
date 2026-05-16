'use strict';

const fs = require('fs');
const path = require('path');

/**
 * Tiny JSON-file database. Good enough to ship the auth flow without external
 * deps. When traffic grows, swap this module for SQLite/Postgres without
 * touching the rest of the app — only the function signatures need to remain.
 */

const DATA_DIR = path.join(__dirname, '..', 'data');
const USERS_FILE = path.join(DATA_DIR, 'users.json');

function ensureStore() {
  if (!fs.existsSync(DATA_DIR)) {
    fs.mkdirSync(DATA_DIR, { recursive: true });
  }
  if (!fs.existsSync(USERS_FILE)) {
    fs.writeFileSync(USERS_FILE, JSON.stringify({ users: [] }, null, 2));
  }
}

function readAll() {
  ensureStore();
  const raw = fs.readFileSync(USERS_FILE, 'utf8');
  try {
    const parsed = JSON.parse(raw);
    if (!parsed || !Array.isArray(parsed.users)) return { users: [] };
    return parsed;
  } catch {
    return { users: [] };
  }
}

function writeAll(state) {
  ensureStore();
  const tmp = USERS_FILE + '.tmp';
  fs.writeFileSync(tmp, JSON.stringify(state, null, 2));
  fs.renameSync(tmp, USERS_FILE);
}

function findByEmail(email) {
  const norm = String(email || '').trim().toLowerCase();
  return readAll().users.find((u) => u.email === norm) || null;
}

function findByLogin(login) {
  const norm = String(login || '').trim().toLowerCase();
  return readAll().users.find((u) => u.login.toLowerCase() === norm) || null;
}

function findByEmailOrLogin(identifier) {
  return findByEmail(identifier) || findByLogin(identifier);
}

function findById(id) {
  return readAll().users.find((u) => u.id === id) || null;
}

function createUser(user) {
  const state = readAll();
  state.users.push(user);
  writeAll(state);
  return user;
}

module.exports = {
  findByEmail,
  findByLogin,
  findByEmailOrLogin,
  findById,
  createUser,
};
