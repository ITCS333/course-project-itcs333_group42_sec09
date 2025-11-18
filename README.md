[![Review Assignment Due Date](https://classroom.github.com/assets/deadline-readme-button-22041afd0340ce965d47ae6ef1cefeee28c7c493a6346c4f15d667ab976d596c.svg)](https://classroom.github.com/a/p4UBLUhf)
[![Open in Visual Studio Code](https://classroom.github.com/assets/open-in-vscode-2e0aaae1b6195c2367325f4f02e2d04e9abb55f0b24a779b69b11b9e10269abc.svg)](https://classroom.github.com/online_ide?assignment_repo_id=20971652&assignment_repo_type=AssignmentRepo)
# ITCS333 Course Page

The course portal aggregates every deliverable for ITCS333: authentication, student management, learning resources, weekly breakdowns, assignments, and the discussion board.

## Task Ownership

| Task | Owner | Notes |
| :-- | :-- | :-- |
| Task 1 – Homepage, Login, Admin Portal | Abdulla Jaafar Abdulla Alasmawi | Completed with PHP session-backed auth and CRUD |
| Task 2 – Course Resources |  | Uses `server/api/resources.php` (CRUD + comments) |
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

All endpoints live under `server/api` and expect JSON. Authentication relies on PHP sessions, so `fetch` calls must include `credentials: 'include'`.

| Endpoint | Methods | Description |
| :-- | :-- | :-- |
| `login.php` | `POST` | Authenticates a user and creates a session |
| `logout.php` | `POST` | Destroys the active session |
| `session.php` | `GET` | Returns the logged-in user (if any) |
| `students.php` | `GET/POST/PUT/DELETE` | Admin-only student CRUD backing the portal |
| `password.php` | `POST` | Updates the currently logged-in user password |
| `resources.php` | `GET/POST/PUT/DELETE` + comments | Powers Task 2 |
| `weekly.php` | `GET/POST/PUT/DELETE` + comments | Powers Task 3 |
| `assignments.php` | `GET/POST/PUT/DELETE` + comments | Powers Task 4 |
| `discussions.php` | `GET/POST/PUT/DELETE` + comments | Powers Task 5 |

## Front-end Structure

- `index.html` – public landing page
- `src/auth/login.html` – shared login entry point (teachers + students)
- `src/admin/manage_users.html` – admin portal with password + student management
- `src/resources`, `src/weekly`, `src/assignments`, `src/discussion` – task-specific modules (HTML/JS scaffolding ready to consume the new APIs)

## Hosting

Once the PHP server is configured on Replit, point the hosted URL here for evaluation.
