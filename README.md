[![Review Assignment Due Date](https://classroom.github.com/assets/deadline-readme-button-22041afd0340ce965d47ae6ef1cefeee28c7c493a6346c4f15d667ab976d596c.svg)](https://classroom.github.com/a/p4UBLUhf)
[![Open in Visual Studio Code](https://classroom.github.com/assets/open-in-vscode-2e0aaae1b6195c2367325f4f02e2d04e9abb55f0b24a779b69b11b9e10269abc.svg)](https://classroom.github.com/online_ide?assignment_repo_id=20971652&assignment_repo_type=AssignmentRepo)
# ITCS333 Course Page

The course portal aggregates every deliverable for ITCS333: authentication, student management, learning resources, weekly breakdowns, assignments, and the discussion board.

## Quick Start (local)

```bash
# 1) Install runtime + MySQL (Debian/Ubuntu example)
sudo apt update && sudo apt install -y php php-mysql mysql-server

# 2) Start MySQL and load schema
sudo systemctl start mysql
mysql -u root -p < schema.sql

# 3) Export DB credentials (or edit src/config/database.php)
export DB_HOST=127.0.0.1
export DB_PORT=3306
export DB_NAME=course_db
export DB_USER=root
export DB_PASS=123   # replace with your own

# 4) Serve the app from the repo root
php -S 0.0.0.0:8000 -t .
```

Open `http://localhost:8000/index.html` (or `src/auth/login.html`) and log in with the sample admin user from your seeded DB.

## Database (MySQL)

- Schema file: `schema.sql` (creates `course_db` + tables).
- Import: `mysql -u <user> -p -h <host> -P <port> < schema.sql`
- Env vars read by `src/config/database.php`: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_SOCKET` (optional).

## Replit

The repo includes a `.replit` and `replit.nix` so it boots with PHP + MySQL client:

1) Add Secrets in Replit:
   - `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` (point to an external MySQL; Replit doesn’t ship MySQL server).
2) Import schema into that database: `mysql -u $DB_USER -p -h $DB_HOST -P $DB_PORT < schema.sql`
3) Run: the default Replit run command is `php -S 0.0.0.0:$PORT -t .`
4) Open the web preview; the app will use the env vars for DB connectivity.

## Task Ownership

| Task | Owner | Notes |
| :-- | :-- | :-- |
| Task 1 – Homepage, Login, Admin Portal | Abdulla Jaafar Abdulla Alasmawi | Completed with PHP session-backed auth and CRUD |
| Task 2 – Course Resources | QASIM FAISAL JASIM ALI | Uses `server/api/resources.php` (CRUD + comments) |
| Task 3 – Weekly Breakdown |  | Uses `server/api/weekly.php` (CRUD + comments) |
| Task 4 – Assignments | TBD | Back-end scaffolding ready via `server/api/assignments.php` |
| Task 5 – General Discussion Board | TBD | Back-end scaffolding ready via `server/api/discussions.php` |

## Live Demo

Replit deployment: _TBD_

## Local Development

### Requirements

- PHP 8.1+ (with PDO + MySQL extensions)
- MySQL 8.x (or compatible MariaDB)
- Any static file server (the PHP built-in server works for both PHP + static assets)

### Setup Steps

1. **Install dependencies**
   ```bash
   sudo apt install php php-mysql mysql-server
   ```
2. **Create the database and tables**
   ```bash
   mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS itcs333_course CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   mysql -u root -p itcs333_course < server/schema.sql
   mysql -u root -p itcs333_course < server/seed.sql
   ```
3. **Configure credentials** (optional)  
   Update `server/config.php` or export `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`.
4. **Run the PHP development server from the project root**
   ```bash
   php -S localhost:8000 -t .
   ```
5. **Open the site**  
   Visit `http://localhost:8000/index.html`. Use the navigation links or open `src/auth/login.html` directly.

### Running the project (quick checklist)

1. **Start MySQL** – `sudo systemctl start mysql` (or your OS equivalent) and confirm it is running with `systemctl status mysql`.
2. **Ensure the schema exists** – run `mysql -u root -p itcs333_course < server/schema.sql` and `mysql -u root -p itcs333_course < server/seed.sql` if the tables or sample data are missing.
3. **Update credentials** – edit `server/config.php` (or set env vars) so `db_user`/`db_pass` match the MySQL account you just used.
4. **Serve the app** – from the repo root run `php -S localhost:8000 -t .`; keep this process running.
5. **Test** – open `http://localhost:8000/src/auth/login.html`, log in with the sample admin credentials, and verify pages hit the PHP APIs successfully.

### Sample Credentials

| User | Email | Password |
| :-- | :-- | :-- |
| Admin | `teacher@example.com` | `Password123!` |
| Student (example) | `202101234@stu.uob.edu.bh` | `Password123!` |

## Backend / API Overview

All PHP APIs live under `src/auth/api` (auth/session) and `src/admin/api` (students/resources/weeks/assignments/discussion). Endpoints expect JSON and use PHP sessions, so `fetch` calls must include `credentials: 'include'`.

Key endpoints:
- `src/auth/api/index.php` – login
- `src/auth/api/logout.php` – logout
- `src/auth/api/session.php` – session status
- `src/auth/api/change_password.php` – password update
- `src/admin/api/index.php` – student CRUD + resource/week/assignment/discussion routes

## Front-end Structure

- `index.html` – public landing page
- `src/auth/login.html` – shared login entry point (teachers + students)
- `src/admin/manage_users.html` – admin portal with password + student management
- `src/resources`, `src/weekly`, `src/assignments`, `src/discussion` – task-specific modules (HTML/JS scaffolding ready to consume the new APIs)

## Hosting

Once the PHP server is configured on Replit, point the hosted URL here for evaluation.
