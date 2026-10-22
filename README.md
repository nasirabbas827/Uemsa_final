# University Event Management System

A PHP and MySQL university event portal with admin, organizer, faculty and student areas.

## Features

- Event creation and registration forms
- Venue and resource booking
- Attendance, feedback and announcements
- Registration exports and organizer messaging

## Technology

PHP, MySQL, HTML, CSS and JavaScript.

## Local setup

Clone the repository:

```bash
git clone https://github.com/nasirabbas827/Uemsa_final.git
cd Uemsa_final
```

1. Install a local PHP web server and MySQL, such as XAMPP.
2. Place the project in the server's document root, or configure a virtual host pointing to this folder.
3. Import `Database/uems_db.sql` using phpMyAdmin or your MySQL client.
4. Update the database connection in `config.php` and in role-specific copies of `config.php` to match your local database name and credentials.
5. Start Apache and MySQL, then open `http://localhost/Uemsa_final/`.

Email verification and notification features require your own SMTP settings in the relevant mail handlers.

## Repository layout

| Path | Purpose |
|---|---|
| `Database/` | SQL database export |
| `PHPMailer/` | Bundled email library |
| `admin/` | Role-specific pages |
| `config.php` | Database connection configuration |
| `css/` | Project resources |
| `faculty/` | Role-specific pages |
| `images/` | Project resources |
| `index.php` | Web entry page |
| `organizer/` | Role-specific pages |
| `student/` | Role-specific pages |

## Development notes

Setup steps describe local development. Configure your own database, mail and API credentials where applicable. Review debug settings and replace development secrets before deploying. The application was not executed as part of this documentation update.

Maintained by [Nasir Abbas](https://github.com/nasirabbas827).
