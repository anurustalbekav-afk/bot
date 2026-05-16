'use strict';

const crypto = require('crypto');

/**
 * Password hashing with scrypt (built into Node) — no external deps required.
 * Stored format: scrypt$N$r$p$saltHex$hashHex
 */

const SCRYPT_N = 16384;
const SCRYPT_R = 8;
const SCRYPT_P = 1;
const KEY_LEN = 64;

function hashPassword(password) {
  return new Promise((resolve, reject) => {
    const salt = crypto.randomBytes(16);
    crypto.scrypt(
      password,
      salt,
      KEY_LEN,
      { N: SCRYPT_N, r: SCRYPT_R, p: SCRYPT_P },
      (err, derived) => {
        if (err) return reject(err);
        resolve(
          `scrypt$${SCRYPT_N}$${SCRYPT_R}$${SCRYPT_P}$${salt.toString('hex')}$${derived.toString('hex')}`,
        );
      },
    );
  });
}

function verifyPassword(password, stored) {
  return new Promise((resolve, reject) => {
    if (!stored || typeof stored !== 'string') return resolve(false);
    const parts = stored.split('$');
    if (parts.length !== 6 || parts[0] !== 'scrypt') return resolve(false);
    const N = Number(parts[1]);
    const r = Number(parts[2]);
    const p = Number(parts[3]);
    const salt = Buffer.from(parts[4], 'hex');
    const expected = Buffer.from(parts[5], 'hex');
    crypto.scrypt(password, salt, expected.length, { N, r, p }, (err, derived) => {
      if (err) return reject(err);
      try {
        resolve(crypto.timingSafeEqual(derived, expected));
      } catch {
        resolve(false);
      }
    });
  });
}

/**
 * Compact, signed session token: base64url(payload).base64url(hmac)
 * Payload: { sub: userId, exp: unixSeconds, iat: unixSeconds }
 */

function b64urlEncode(buf) {
  return Buffer.from(buf).toString('base64').replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}

function b64urlDecode(str) {
  const pad = str.length % 4 === 0 ? '' : '='.repeat(4 - (str.length % 4));
  return Buffer.from(str.replace(/-/g, '+').replace(/_/g, '/') + pad, 'base64');
}

function signSession(userId, secret, ttlSeconds) {
  const now = Math.floor(Date.now() / 1000);
  const payload = { sub: userId, iat: now, exp: now + ttlSeconds };
  const payloadB64 = b64urlEncode(JSON.stringify(payload));
  const sig = crypto.createHmac('sha256', secret).update(payloadB64).digest();
  return `${payloadB64}.${b64urlEncode(sig)}`;
}

function verifySession(token, secret) {
  if (!token || typeof token !== 'string') return null;
  const idx = token.indexOf('.');
  if (idx < 0) return null;
  const payloadB64 = token.slice(0, idx);
  const sigB64 = token.slice(idx + 1);
  const expectedSig = crypto.createHmac('sha256', secret).update(payloadB64).digest();
  let providedSig;
  try {
    providedSig = b64urlDecode(sigB64);
  } catch {
    return null;
  }
  if (providedSig.length !== expectedSig.length) return null;
  if (!crypto.timingSafeEqual(providedSig, expectedSig)) return null;
  let payload;
  try {
    payload = JSON.parse(b64urlDecode(payloadB64).toString('utf8'));
  } catch {
    return null;
  }
  if (!payload || typeof payload !== 'object') return null;
  if (typeof payload.exp !== 'number' || payload.exp < Math.floor(Date.now() / 1000)) return null;
  return payload;
}

module.exports = { hashPassword, verifyPassword, signSession, verifySession };
