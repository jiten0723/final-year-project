# EDUCORE - Full-Stack E-Learning Platform

EDUCORE is a modern, responsive e-learning web application built with HTML, CSS, JavaScript, and PHP with MySQL. It features user authentication, role-based dashboards (Admin, Teacher, Student), course management, interactive quizzes, and mock payment integrations.

## Features

-   **User Authentication**: Login and registration with distinct roles (Admin, Teacher, Student).
-   **Role-Based Dashboards**:
    -   **Admin**: View platform stats, approve/reject courses, manage users, view payments and reviews.
    -   **Teacher**: Create courses, add lessons, view enrolled students, and track earnings.
    -   **Student**: Browse courses, track progress, take quizzes, and earn certificates.
-   **Course Management**: Free and premium courses. Lessons can be text-based or video links. Free previews available.
-   **Interactive Learning**:
    -   MCQ Quizzes with an AI adaptive mode (difficulty adjusts based on answers).
    -   Interactive Word Match game.
-   **Payment Integration (Mock)**: eSewa and PayPal sandbox simulations.
-   **Certificates**: Auto-generated certificates with a unique ID upon course completion.
-   **Modern UI/UX**: Clean, responsive design with dynamic gradients, hover effects, and toast notifications.

## Project Structure

```
project/
├── api/                  # API endpoints (lessons, notifications, quizzes, recommendations)
├── assets/
│   ├── css/              # Main stylesheet (style.css)
│   └── js/               # Main JavaScript and Quiz engine
├── certificates/         # Certificate generation and verification (mock)
├── config/               # Database configuration
├── courses/              # Course listing, detail, and enrollment
├── dashboard/            # Role-specific dashboards (admin.php, teacher.php, student.php)
├── database/             # Database schema and seed data (educore.sql)
├── includes/             # Shared components (header.php, footer.php, auth.php)
├── payment/              # Payment checkout, success, cancel, and processors
├── quiz/                 # Quiz listing, interactive MCQ, and word game
├── index.php             # Homepage
├── login.php             # User login
├── register.php          # User registration
├── logout.php            # User logout
└── README.md             # Project documentation
```

## Setup Instructions

1.  **Environment Setup**:
    -   Ensure you have a local web server running with PHP and MySQL (e.g., XAMPP, WAMP, or MAMP).
2.  **Database Configuration**:
    -   The application expects a MySQL database.
    -   The provided `educore.sql` script creates the `educore` database and seeds it with demo data.
    -   *(The database has already been imported into your environment).*
    -   Database connection details are defined in `config/db.php`. Ensure they match your local server (Default: Host: `localhost`, User: `root`, Password: ``, Name: `educore`).
3.  **Application URL**:
    -   The `BASE_URL` is defined in `config/db.php`. It is set to `http://localhost/project`. Ensure your application is served from this path.
4.  **Accessing the Application**:
    -   Open your web browser and navigate to `http://localhost/project/index.php`.

## Demo Accounts

The database comes pre-seeded with the following demo accounts (Password for all: `demo123`):
-   **Admin**: `admin@educore.com`
-   **Teacher**: `teacher@educore.com`
-   **Student**: `student@educore.com`

You can also use the "Quick Demo Login" buttons on the login page.

## Notes

-   This project uses a custom-built CSS framework with CSS variables for a consistent theme, avoiding the need for a compile step while maintaining a modern aesthetic.
-   Payment gateways (eSewa, PayPal) and "Google" login are simulated for demonstration purposes.

Enjoy using EDUCORE!
