# 📋 Attendance & Access Logging System

A lightweight, secure, and production-ready web application built with **PHP (PDO)**, **MySQL**, and **Bootstrap 5**. Designed for classrooms, offices, and event monitoring, this system provides role-based access for students and administrators, automated check-in/check-out tracking with grace-period schedule enforcement, dynamic status updates, and one-click CSV reporting.

---

## 🌟 Key Features

### 👤 User / Student Dashboard
* **Real-time Status Tracking:** Dynamic badges indicating active status (`Not Checked In`, `Checked In at HH:MM`, `Checked Out`).
* **Automated Schedule Enforcement:** Automatically flags attendance logs as **On-Time** or **Late** based on configured schedule start times and grace periods.
* **Personal Attendance History:** View past sign-ins and sign-outs with date-stamped records.

### 🛡️ Admin Dashboard & Analytics
* **System Overview Cards:** Quick view of total registered users, today's total present count, and today's late arrivals.
* **Filter Controls:** Filter logs by custom date ranges (`start_date` to `end_date`) and search by student ID or full name.
* **One-Click CSV Export:** Stream log reports directly into downloadable CSV spreadsheets based on active filters.

### 🔒 Security & Architecture
* **PDO Prepared Statements:** Complete protection against SQL Injection attacks.
* **Password Hashing:** BCRYPT hashing (`password_hash()` and `password_verify()`).
* **Input Sanitization & Session Middleware:** XSS protection and strict role authorization checks (`admin` vs `user`).

---

## 🛠️ Tech Stack

* **Backend:** PHP 8.x (Native PDO extension)
* **Database:** MySQL / MariaDB (via XAMPP / phpMyAdmin)
* **Frontend:** Bootstrap 5, FontAwesome 6, HTML5, CSS3, JavaScript (Fetch / Vanilla)
* **Server:** Apache (XAMPP Environment)

---

## 📁 Folder Structure

```text
attendance_system/
├── actions/              # Request handlers and form logic
│   ├── checkin_action.php
│   ├── checkout_action.php
│   ├── login_action.php
│   └── register_action.php
├── assets/               # Static assets
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── main.js
├── config/               # System configurations
│   ├── auth.php          # Session middleware & authorization
│   └── db.php            # PDO Database connection singleton
├── export/               # Reporting endpoints
│   └── export_csv.php    # CSV streaming script
├── views/                # Reusable UI components
│   ├── footer.php
│   └── header.php
├── admin_dashboard.php   # Admin management panel
├── user_dashboard.php    # User check-in portal
├── index.php             # Primary router
├── login.php             # Authentication page
├── logout.php            # Session termination
├── register.php         # Registration page
└── schema.sql            # Database migration & seed script