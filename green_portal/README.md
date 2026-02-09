# Campus Green Innovation Portal

A simple web application for students and staff to submit eco-friendly ideas, and for admins to approve, assign, and track them.

## Tech Stack

- **Frontend:** HTML, CSS, JavaScript  
- **Backend:** PHP (no frameworks)  
- **Database:** MySQL  
- **Server:** XAMPP  
- **Database access:** mysqli  
- **Authentication:** Session-based login  

## Setup (XAMPP)

1. Copy the `green_portal` folder to `C:\xampp\htdocs\green_portal` (or your XAMPP `htdocs` path).

2. Start **Apache** and **MySQL** in XAMPP Control Panel.

3. Create the database:
   - Open phpMyAdmin: http://localhost/phpmyadmin
   - Go to the **SQL** tab
   - **Paste the full SQL from `database/setup.sql`** (open the file, copy all its contents, paste into the SQL box). Do **not** paste the file path like `C:\...\setup.sql` — only the SQL code.
   - Click **Go**.

4. Database connection is in `config/db.php` (localhost, user: root, no password). Change if your MySQL setup is different.

5. Open the app: **http://localhost/green_portal/**

## First Use

- **Register** a user (choose role: Student, Staff, or Admin).
- **Login** – Admin → Admin Dashboard; **Staff** → Staff Dashboard; **Student** → User Dashboard.
- **Student:** Submit ideas (Title, Description, Category: Waste / Energy / Water / Greenery), view your ideas.
- **Staff:** See only ideas **assigned to you**; view details, update status (In Progress / Completed), add remarks.
- **Admin:** View all ideas, Approve, **Assign to staff** (dropdown), and Update status.

### Staff module (sample user)

1. If your database was created with an older `setup.sql`, run **`database/staff_module_migration.sql`** in phpMyAdmin (adds `assigned_staff_id`, `staff_remarks` to `ideas`).
2. Create sample staff and assign ideas: open **http://localhost/green_portal/database/create_staff_sample.php** in the browser once.
3. Log in as **staff@college.com** / **staff123** to use the Staff Dashboard.

## Security

- Passwords are hashed with `password_hash()` (PHP).
- All database queries that use user input use **prepared statements** to prevent SQL injection.

## Folder Structure

```
green_portal/
├── index.php          (redirects to login or dashboard)
├── login.php
├── register.php
├── logout.php
├── dashboard.php
├── staff_dashboard.php
├── submit_idea.php
├── view_ideas.php
├── view_idea.php
├── assign_idea.php
├── update_status.php
├── admin/
│   ├── admin_dashboard.php
│   └── approve_idea.php
├── config/
│   └── db.php
├── database/
│   └── setup.sql
├── assets/
│   ├── css/style.css
│   └── js/script.js
└── README.md
```
