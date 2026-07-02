# Chrisland University Course Registration System

A web-based course registration management system for Chrisland University, allowing students to register for courses and administrators to manage the academic program.

## System Overview

**URL:** http://localhost/course_registration/

**Default Admin Credentials:**
- Username: `admin`
- Password: `admin123`

## Features

### Student Portal
- **Course Registration** - Register for available courses (max 24 credit units)
- **My Courses** - View registered courses and their status
- **Profile** - View personal information
- **Course Slip Download** - Generate registration slip

### Admin Panel
- **Dashboard** - Overview statistics (total students, courses, pending approvals)
- **Manage Courses** - Add, edit, and delete courses
- **Student Management** - View and manage student records
- **Registrations/Approvals** - Approve or reject course registrations
- **Payments** - Manage student payment records

## Project Structure

```
course_registration/
├── index.php                    # Entry point (redirects to login)
├── README.md                    # This file
│
├── auth/                        # Authentication pages
│   ├── login.php               # Login page with role selection
│   └── register.php            # Student registration
│
├── admin/                       # Admin dashboard pages
│   ├── dashboard.php           # Admin overview
│   ├── courses.php             # Course management
│   ├── students.php            # Student records
│   ├── payments.php            # Payment management
│   └── approvals.php           # Registration approvals
│
├── student/                     # Student portal pages
│   ├── dashboard.php           # Student overview
│   ├── profile.php             # Student profile
│   ├── my_courses.php          # Registered courses
│   └── register_courses.php    # Course registration
│
├── includes/                    # Shared components
│   ├── header.php              # Page header/navbar
│   ├── footer.php              # Page footer
│   ├── student_sidebar.php     # Student navigation
│   └── admin_sidebar.php       # Admin navigation
│
├── config/                      # Configuration files
│   └── database.php            # Database connection
│
├── assets/                      # Static assets
│   ├── css/
│   │   └── style.css           # Stylesheet
│   └── js/
│       └── main.js             # JavaScript utilities
│
├── pdf/                         # PDF generation
│   └── generate_slip.php       # Course slip generator
│
├── scripts/                     # Utility scripts
│   └── setup_database.php      # Database setup script
│
└── photo_2026-07-01_03-44-37.jpg # University logo
```

## Database Schema

The system uses MySQL with the following tables:

| Table | Description |
|-------|-------------|
| `students` | Student records (id, name, matric_no, email, department, level, password) |
| `admins` | Admin users (id, name, username, password) |
| `courses` | Course catalog (id, course_code, course_title, units, semester, session) |
| `registrations` | Course registrations (id, student_id, course_id, status, date_registered) |
| `payments` | Payment records (id, student_id, session, amount, status) |

## Setup Instructions

1. **Start XAMPP Services**
   - Start Apache and MySQL services

2. **Initialize Database**
   - Navigate to: `http://localhost/course_registration/scripts/setup_database.php?confirm=1`
   - Or run via CLI: `php scripts/setup_database.php confirm`

3. **Access the System**
   - Open browser to: `http://localhost/course_registration/`

## Security Features

- **CSRF Protection** - All forms include CSRF tokens
- **Password Hashing** - Passwords stored with `password_hash()`
- **Session Management** - Secure session handling with regeneration
- **Prepared Statements** - SQL injection prevention
- **Input Validation** - Server-side validation for all inputs
- **Role-Based Access** - Separate portals for students and admins

## Technical Stack

- **Backend:** PHP 7+
- **Database:** MySQL / MariaDB
- **Frontend:** HTML5, CSS3 (Bootstrap 5), JavaScript
- **PDF Generation:** FPDF library

## Accessibility

The system follows WCAG 2.1 AA standards with:
- Sufficient color contrast ratios
- Semantic HTML structure
- Form labels and placeholders
- Responsive design for all devices