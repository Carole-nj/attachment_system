# Attachment Management System (web version)

PHP + MySQL system for industrial attachment: students sign in and out daily, supervisors review their logs, and coordinators/admins track how many weeks are done, how many remain and the total duration of each placement.

## Setup on XAMPP (Windows)

1. Copy the `attachment_system` folder to `C:\xampp\htdocs\`.
2. Open the **XAMPP Control Panel** and start **Apache** and **MySQL**.
3. Open `http://localhost/phpmyadmin`, click **Import**, choose `database.sql` and click **Go**. This creates the `attachment_db` database with demo data.
4. Open `http://localhost/attachment_system/` in your browser.

If your MySQL root user has a password, set it in `includes/config.php`.

## Demo logins (password for all: `Pass1234`)

| Role        | Email                     |
|-------------|---------------------------|
| Admin       | admin@attach.local        |
| Coordinator | coordinator@attach.local  |
| Supervisor  | supervisor@attach.local   |
| Student     | student@attach.local      |

Change these passwords (Admin > Users) or delete the demo accounts before real use.

## What each role can do

- **Student**: register an account, sign in and sign out each day (with a short description of the work done), see weeks done, weeks remaining, days left, hours logged and the total duration of the attachment.
- **Industry supervisor**: see assigned students and their progress, approve or reject daily logs with comments.
- **Coordinator**: add companies, assign students to companies and supervisors, set start/end dates, monitor everyone's progress, print placement reports.
- **Admin**: everything the coordinator can do, plus add/disable/delete users and reset passwords.

## How progress is calculated

- Total duration = days from start date to end date, shown in weeks.
- Weeks done = completed 7-day blocks since the start date.
- Weeks remaining = days left until the end date, rounded up to whole weeks.
- Students can only sign in between the start and end dates of their placement.
