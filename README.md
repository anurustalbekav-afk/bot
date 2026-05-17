# LAMBADA BONUS

CRMP RolePlay project website. Landing page + user account system.

## Stack

- Node.js + Express
- SQLite (better-sqlite3)
- Vanilla HTML/CSS/JS
- JWT auth via httpOnly cookie

## Run

```bash
cp .env.example .env
npm install
npm start
```

Open http://localhost:3000

## Project structure

```
server.js              # Express app entry
src/
  db.js                # SQLite schema + seed
  auth.js              # /api/register, /api/login, /api/me, /api/logout
  validate.js          # input validation
public/
  index.html           # landing page
  login.html
  register.html
  dashboard.html       # account page
  assets/
    styles.css
    app.js
    auth.js
```

## API

| Method | Path           | Description           |
|--------|----------------|-----------------------|
| POST   | /api/register  | Create account        |
| POST   | /api/login     | Login (sets cookie)   |
| POST   | /api/logout    | Clear cookie          |
| GET    | /api/me        | Current user          |
| GET    | /api/servers   | Server list + online  |
| GET    | /api/news      | News feed             |
