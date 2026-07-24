# 🎓 EDUCORE — Complete Developer Guide

> **Written so simply that even a 12-year-old can understand it.**
> If you can read this, you can build (and rebuild) this entire website.

---

## 📋 Table of Contents

1. [What is EDUCORE?](#1-what-is-educore)
2. [What Tech Stack is Used?](#2-what-tech-stack-is-used)
3. [Folder Structure — Every File Explained](#3-folder-structure--every-file-explained)
4. [The Database — Every Table Explained](#4-the-database--every-table-explained)
5. [How Routing Works (.htaccess)](#5-how-routing-works-htaccess)
6. [How Authentication Works](#6-how-authentication-works)
7. [The Three User Roles](#7-the-three-user-roles)
8. [How a Student Uses the Site](#8-how-a-student-uses-the-site)
9. [How a Teacher Uses the Site](#9-how-a-teacher-uses-the-site)
10. [How an Admin Uses the Site](#10-how-an-admin-uses-the-site)
11. [How Payments Work](#11-how-payments-work)
12. [How Certificates Work](#12-how-certificates-work)
13. [How Quizzes Work](#13-how-quizzes-work)
14. [How AI Recommendations Work](#14-how-ai-recommendations-work)
15. [How the Frontend (CSS + JS) Works](#15-how-the-frontend-css--js-works)
16. [How Email (OTP) Works](#16-how-email-otp-works)
17. [How Google Login Works](#17-how-google-login-works)
18. [Clean URLs Explained](#18-clean-urls-explained)
19. [Configuration — Every Setting](#19-configuration--every-setting)
20. [How to Set Up From Scratch](#20-how-to-set-up-from-scratch)

---

## 1. What is EDUCORE?

EDUCORE is an **online learning platform** — think of it like a mini-Udemy built from scratch.

**What can people do on it?**

- 🧑‍🎓 **Students** browse courses, enroll (free or paid), watch lessons, take quizzes, and earn certificates
- 👨‍🏫 **Teachers** create courses, add lessons, upload a signature image, and see their earnings
- 🛡️ **Admins** approve/reject courses, ban/unban users, toggle featured courses, and see all payments

**Key features:**
- Email + OTP verification on every login (2-factor auth)
- Google OAuth login (sign in with Google)
- Trusted device cookie — skip OTP for 60 days on known devices
- eSewa (Nepal digital wallet) + PayPal (sandbox) payment processing
- AI-powered course recommendations based on what you studied
- Adaptive quizzes — difficulty changes based on your answers
- PDF certificates with QR code verification
- Teacher signature image on certificates
- Clean URLs (no `.php` in the address bar)

---

## 2. What Tech Stack is Used?

Think of the tech stack as the ingredients in a recipe:

| Layer | Technology | What it does |
|---|---|---|
| **Language** | PHP 8+ | Runs on the server, builds every page |
| **Database** | MySQL (MariaDB) | Stores all data — users, courses, payments |
| **Frontend HTML** | PHP + HTML5 | Mixed together in `.php` files |
| **Styling** | Custom CSS + Bootstrap 5 | Makes it look pretty |
| **Icons** | Font Awesome 6 | All the little icons everywhere |
| **JavaScript** | Vanilla JS | Animations, quiz engine, filters |
| **Fonts** | Google Fonts (Inter, Poppins) | Nice typography |
| **Email** | PHPMailer + Gmail SMTP | Sends OTP emails |
| **Server** | Apache via XAMPP | Runs PHP locally |
| **URL Routing** | Apache mod_rewrite (.htaccess) | Makes clean URLs work |
| **OAuth** | Google OAuth 2.0 | "Sign in with Google" |
| **QR Codes** | api.qrserver.com (external API) | Certificate verification QR |
| **Image Upload** | ImgBB API (external) | Course thumbnail + signature uploads |

---

## 3. Folder Structure — Every File Explained

```
edu-core/
│
├── index.php               ← Homepage (hero, featured courses, categories)
├── login.php               ← Login page (email+password OR Google)
├── register.php            ← Registration page (student or teacher)
├── logout.php              ← Destroys session, redirects to homepage
├── otp_verify.php          ← OTP email verification page
├── migrate.php             ← Adds new DB columns (run once after updates)
├── .htaccess               ← Apache URL rewriting rules (clean URLs)
├── .env                    ← Secret keys (Google OAuth credentials)
├── composer.json           ← PHP package manager config (for PHPMailer)
│
├── config/
│   └── db.php              ← Database connection + ALL site constants
│
├── includes/
│   ├── auth.php            ← ALL authentication functions (login, register, etc.)
│   ├── header.php          ← Top navbar — included on every page
│   ├── footer.php          ← Footer — 3 versions: public, teacher, admin
│   └── mailer.php          ← Email sending functions (OTP + Welcome email)
│
├── dashboard/
│   ├── student.php         ← Student dashboard (courses, quizzes, certs, recs)
│   ├── teacher.php         ← Teacher dashboard (create course, add lessons, earnings)
│   └── admin.php           ← Admin panel (approve courses, manage users)
│
├── courses/
│   ├── index.php           ← Browse all courses (filter, search, sort)
│   ├── detail.php          ← Single course page (description, lessons preview)
│   ├── enroll.php          ← Handles enrollment (free) or redirects to payment
│   └── learn.php           ← Lesson viewer (marks progress, shows content)
│
├── payment/
│   ├── checkout.php        ← Payment page (choose eSewa or PayPal)
│   ├── esewa_process.php   ← Processes eSewa payment, creates enrollment
│   ├── paypal_process.php  ← Processes PayPal payment, creates enrollment
│   ├── success.php         ← Payment success page
│   └── cancel.php          ← Payment cancelled page
│
├── certificates/
│   ├── generate.php        ← Creates + displays certificate (web + print/PDF)
│   └── verify.php          ← Verifies a certificate by its code
│
├── quiz/
│   ├── index.php           ← Quiz selection page + runs the quiz
│   └── game.php            ← Word Match interactive game
│
├── api/
│   ├── recommendations.php ← AI recommendation engine (returns JSON)
│   ├── notifications.php   ← Loads/marks-read notifications (returns JSON)
│   ├── save_quiz_result.php← Saves quiz score to DB (called from JS)
│   ├── get_lesson.php      ← Returns lesson content as JSON (AJAX)
│   ├── upload_image.php    ← Uploads image to ImgBB, returns URL
│   └── google_auth.php     ← Handles Google OAuth callback
│
├── assets/
│   ├── css/
│   │   └── style.css       ← ALL custom styling for the entire site
│   ├── js/
│   │   ├── main.js         ← Animations, toast notifications, star rating, etc.
│   │   └── quiz.js         ← Client-side adaptive quiz engine
│   └── images/
│       ├── favicon.ico     ← Browser tab icon
│       └── verified-stamp.png ← Used on certificate verify page
│
├── database/
│   └── educore.sql         ← Full database schema + seed data (import this first!)
│
└── vendor/                 ← PHPMailer library (installed via Composer)
```

---

## 4. The Database — Every Table Explained

The database is called `educore`. Think of each table as a spreadsheet.

### `users` — Everyone who has an account

| Column | Type | What it stores |
|---|---|---|
| `id` | INT | Unique number for each user |
| `name` | VARCHAR | Full name |
| `email` | VARCHAR | Email address (must be unique) |
| `password` | VARCHAR | Bcrypt hashed password (never plain text!) |
| `role` | ENUM | `admin`, `teacher`, or `student` |
| `is_active` | TINYINT | 1=can login, 0=banned |
| `is_verified` | TINYINT | 1=OTP verified, 0=must verify first |
| `google_id` | VARCHAR | Google account ID (if logged in via Google) |
| `signature_image` | VARCHAR | URL of teacher's uploaded signature image |

### `courses` — Every course on the platform

| Column | What it stores |
|---|---|
| `title` | Course name |
| `slug` | URL-friendly name, e.g. `javascript-mastery` |
| `instructor_id` | Which teacher made it (links to `users.id`) |
| `price` | 0.00 = free, anything else = premium |
| `type` | `free` or `premium` |
| `status` | `pending` (waiting for admin), `approved` (live), `rejected` |
| `is_featured` | 1 = shows on homepage |
| `thumbnail` | URL of course cover image |

### `lessons` — Each lesson inside a course

Every course can have many lessons. Lessons have `order_num` so they appear in the right order.

### `enrollments` — Who is taking which course

| Column | What it stores |
|---|---|
| `user_id` | The student |
| `course_id` | The course |
| `progress` | 0–100% how far through the course |
| `status` | `active`, `completed`, or `cancelled` |

### `lesson_progress` — Which specific lessons a student finished

Tracks per-lesson completion. Progress % = completed lessons ÷ total lessons × 100.

### `payments` — Every purchase

| Column | What it stores |
|---|---|
| `method` | `esewa`, `paypal`, or `free` |
| `status` | `pending`, `completed`, `failed` |
| `transaction_id` | ID from payment provider |

### `quizzes` — Quiz definitions

| Column | What it stores |
|---|---|
| `is_adaptive` | 1 = AI adjusts difficulty, 0 = fixed |
| `pass_percentage` | Minimum % to pass (e.g. 60) |

### `quiz_questions` — Individual MCQ questions

Each question has 4 options (A, B, C, D), one correct answer, a difficulty level, and an explanation shown after answering.

### `quiz_results` — Student quiz scores saved permanently

### `certificates` — Issued certificates

| Column | What it stores |
|---|---|
| `certificate_code` | Unique ID like `EDUCORE-A1B2C3D4E5` |
| `issued_at` | When it was first generated |

### `notifications` — In-app notification messages

### `otp_codes` — One-time passwords for email verification

| Column | What it stores |
|---|---|
| `code` | 6-digit number |
| `expires_at` | Expires 10 minutes after creation |
| `used` | 1 = already used (can't reuse!) |

### `trusted_devices` — Devices that can skip OTP for 60 days

Stores a secure 64-character token in a browser cookie.

---

## 5. How Routing Works (.htaccess)

Normally, PHP websites have ugly URLs like `courses/detail.php?slug=javascript-mastery`.

EDUCORE uses Apache's `mod_rewrite` to turn ugly URLs into clean ones **behind the scenes**. The browser sees a clean URL but Apache secretly loads the real PHP file.

```
What browser sees                    →   What Apache actually loads
─────────────────────────────────────────────────────────────────────
/edu-core/Homepage                   →   index.php
/edu-core/dashboard/admin            →   dashboard/admin.php
/edu-core/dashboard/teacher          →   dashboard/teacher.php
/edu-core/dashboard/student          →   dashboard/student.php
/edu-core/courses/javascript-mastery →   courses/detail.php?slug=javascript-mastery
/edu-core/courses/type=free          →   courses/index.php?type=free
/edu-core/certificates/verify/EDUCORE-ABC123  →  certificates/verify.php?code=EDUCORE-ABC123
/edu-core/certificates/generate/my-course     →  certificates/generate.php?slug=my-course
/edu-core/quiz/anything              →   quiz/index.php
```

**The rules always check real files/directories first.** If the URL points to an actual file (like `assets/css/style.css`), Apache serves it directly and skips the rewrite rules.

---

## 6. How Authentication Works

This is one of the most important parts to understand. Here is the full flow:

### Registration Flow

```
User fills register form
        ↓
registerUser() in auth.php
        ↓
Checks: email not already taken
        ↓
Saves user to DB with is_verified=0 (can't access dashboard yet!)
        ↓
Generates random 6-digit OTP code → saves to otp_codes table
        ↓
Sends OTP to user's email via PHPMailer/Gmail
        ↓
Redirects to otp_verify.php
        ↓
User enters OTP → system checks it matches + not expired (10 min)
        ↓
Sets is_verified=1 → saves trusted_device cookie (60 days)
        ↓
Redirects to dashboard
```

### Login Flow

```
User enters email + password
        ↓
loginUser() in auth.php
        ↓
Checks: password matches the hashed one in DB
        ↓
Checks: is there an 'educore_trusted' cookie for this device?
    ├── YES (device known) → skip OTP, go straight to dashboard ✅
    └── NO (unknown device) → generate new OTP, send email, go to otp_verify.php
```

### Key Security Rules

- Passwords are **never stored as plain text** — always hashed with `password_hash()` (bcrypt)
- OTPs **expire after 10 minutes** and can only be used **once**
- Every page that requires login calls `requireLogin()` — if not logged in, redirects to login page
- Every dashboard checks the user's **role** — a student can't access teacher or admin pages

---

## 7. The Three User Roles

Every user has exactly one role stored in `users.role`:

| Role | What they can do | Their dashboard |
|---|---|---|
| `student` | Browse, enroll, learn, take quizzes, get certificates | `dashboard/student.php` |
| `teacher` | Create courses, add lessons, upload signature, see earnings | `dashboard/teacher.php` |
| `admin` | Approve/reject courses, ban users, see all data | `dashboard/admin.php` |

**Admins can access everything.** The role check `requireRole('teacher')` allows admins through too.

---

## 8. How a Student Uses the Site

Here is the complete journey of a student, step by step:

### Step 1 — Browse Courses
`/courses/index.php` — Shows all approved courses. Can filter by:
- Type (Free / Premium)
- Level (Beginner / Intermediate / Advanced)
- Category (Programming, Design, etc.)
- Search by title or instructor name
- Sort by popularity, newest, rating, price

### Step 2 — View Course Detail
`/courses/[slug]` → `courses/detail.php`  
Shows full description, lessons list, instructor info, reviews.  
If already enrolled → shows "Continue Learning" button.

### Step 3 — Enroll

**Free course:**
```
Click "Enroll Free"
  → courses/enroll.php
  → INSERT into enrollments (status='active', progress=0)
  → INSERT into payments (method='free', amount=0)
  → Notification: "You're enrolled in X!"
  → Redirect to course detail page
```

**Premium course:**
```
Click "Enroll Now"
  → courses/enroll.php detects type='premium'
  → Redirects to payment/checkout.php
  → [See Payment section below]
```

### Step 4 — Learn
`/courses/learn.php?course_id=X&lesson_id=Y`
- Shows lesson content (text + optional video)
- Auto-marks each visited lesson as completed
- Updates `progress` in enrollments (completed/total × 100)
- Sidebar shows all lessons with checkmarks for completed ones
- Previous/Next lesson navigation

### Step 5 — Take Quizzes
`/quiz/` — Select a quiz → MCQ questions appear one at a time  
After finishing → score is saved to `quiz_results` table

### Step 6 — Get Certificate
When progress = 100%, a "Get Certificate" button appears.  
`/certificates/generate/[course-slug]` — generates a PDF-ready certificate with:
- Student's name
- Course name
- Issue date
- QR code linking to the verification page
- Instructor signature image (if uploaded)

### Student Dashboard Tabs

| Tab | What it shows |
|---|---|
| Overview | Stats + performance score + continue learning + notifications |
| My Courses | All enrolled courses with progress bars |
| Quiz Results | All past quiz scores and pass/fail status |
| Certificates | All earned certificates with verify + view buttons |
| Recommended | AI-suggested courses personalized to the student |

### Performance Score Formula

The dashboard shows a circular gauge with a score out of 100:

```
Score = (Quiz Average % × 0.40)
      + (Course Progress Average % × 0.35)
      + (Completion Rate % × 0.25)
```

Grade thresholds: A (90+), B (75+), C (60+), D (below 60)

---

## 9. How a Teacher Uses the Site

### Creating a Course

```
Teacher goes to Teacher Dashboard → "Create Course" tab
  → Fills form: title, description, category, price, level, duration, thumbnail
  → Submits form → POST to teacher.php
  → Course saved to DB with status='pending'
  → Admin gets notified to review it
```

Course is **not visible** to students until admin approves it.

### Teacher Dashboard Tabs

| Tab | What it does |
|---|---|
| Overview | Stats: total courses, students, revenue |
| My Courses | Table of all courses with view/edit buttons |
| Create Course | Form to submit a new course |
| Add Lessons | Adds lessons to any approved course |
| Students | Table of all enrolled students + their progress |
| Earnings | All payments from students for teacher's courses |
| Signature | Upload signature image (appears on certificates) |

### Uploading a Signature

```
Teacher goes to Signature tab
  → Clicks upload area → picks image file
  → Browser sends file to api/upload_image.php via fetch()
  → PHP uploads to ImgBB API → gets back a public URL
  → URL stored in users.signature_image
  → On any certificate for this teacher's courses → signature image appears above the line
```

---

## 10. How an Admin Uses the Site

### Approving a Course

```
Admin sees badge on sidebar: "Pending Review (3)"
  → Goes to Pending Review tab
  → Sees all courses waiting for approval
  → Clicks "Approve" → POST form → status set to 'approved'
  → Teacher gets notification: "Your course X has been approved!"
  → Course now appears on the public course listing
```

### Admin Dashboard Tabs

| Tab | What it does |
|---|---|
| Overview | All platform stats + recent users + recent reviews |
| Pending Review | Courses waiting for approval/rejection |
| All Courses | Full course list with featured toggle (⭐) |
| Users | All users with ban/unban buttons |
| Payments | All completed payment transactions |
| Reviews | All student reviews across all courses |

---

## 11. How Payments Work

### Free Courses
No payment needed. `courses/enroll.php` directly creates the enrollment.

### Premium Courses — eSewa

eSewa is Nepal's most popular digital wallet. The integration uses eSewa's sandbox (test) environment.

```
Student clicks "Enroll Now" on premium course
  → checkout.php shows payment options
  → Student clicks "eSewa" → form submits to payment/esewa_process.php
  → esewa_process.php:
      1. Simulates eSewa verification (in real production: calls eSewa verify API)
      2. Generates transaction ID: ESEWA-[random]
      3. INSERTs into payments table (status='completed')
      4. INSERTs into enrollments table (status='active')
      5. Sends notification to student
      6. Redirects to payment/success.php
```

### Premium Courses — PayPal

Same flow but through `paypal_process.php`. Uses PayPal sandbox credentials.

### Payment Table Entry

Every enrollment (even free ones) creates a payment record:
- Free enrollments: `method='free'`, `amount=0`, `status='completed'`
- eSewa: `method='esewa'`, `status='completed'`
- PayPal: `method='paypal'`, `status='completed'`

---

## 12. How Certificates Work

### Generating a Certificate

```
Student visits /certificates/generate/[course-slug]
  → generate.php checks:
      1. Is the student logged in? (requireLogin)
      2. Is the student enrolled in this course? (check enrollments table)
  → Checks if certificate already exists for this user+course
      ├── YES → loads existing certificate
      └── NO  → creates certificate_code = "EDUCORE-" + random 10-char hash
               → saves to certificates table
  → Shows certificate on screen (web preview)
  → Also has "Print / Save PDF" button
```

### Certificate Design (Web + Print)
Both the web preview and print mode are in the same file (`generate.php`).

Print mode (`?print=1` in URL):
- Outputs a standalone HTML page (no navbar/footer)
- CSS `@page { size: A4 landscape; margin: 0; }`
- `window.onload = () => window.print()` auto-opens print dialog
- Green gradient corner triangles, dot-grid watermark, CERTIFICATE title
- QR code from `api.qrserver.com` linking to verify URL
- Instructor signature image (if uploaded) shown above the line
- Falls back to cursive Dancing Script font if no image

### Verifying a Certificate

```
Anyone visits /certificates/verify/EDUCORE-ABC123
  → verify.php looks up certificate_code in DB
  → If found: shows green "Valid Certificate" card with:
      - Student name, course name
      - Issue date
      - Instructor name
  → If not found: shows "Certificate Not Found" error
```

---

## 13. How Quizzes Work

### Two Types of Quizzes

1. **Regular Quiz** — fixed set of questions, random order
2. **Adaptive Quiz** (`is_adaptive=1`) — AI adjusts difficulty based on your answers

### Taking a Quiz (what happens in the browser)

`quiz/index.php` loads quiz data and embeds it as JavaScript variables:

```javascript
const QUESTIONS = [...]; // all question objects from DB
const IS_ADAPTIVE = true/false;
const PASS_PCT = 60;
```

Then `quiz.js` takes over completely in the browser (no page reloads).

### Adaptive Quiz Logic (inside `quiz.js`)

```
Start with Easy questions
  ↓
If 2 correct in a row → bump up to Medium difficulty
If 2 wrong in a row   → drop back to Easy
  ↓
If on Medium:
  2 correct → bump to Hard
  2 wrong   → drop to Easy
  ↓
When all questions done → show score screen
```

When difficulty increases, `quiz.js` **inserts a new harder question** into the queue right after the current position.

### Saving Quiz Results

When the quiz ends, `quiz.js` sends a `fetch()` POST request to `api/save_quiz_result.php`:

```json
{
  "quiz_id": 2,
  "score": 4,
  "total_questions": 6,
  "percentage": 66.67,
  "passed": 1
}
```

This saves to the `quiz_results` table. The student's performance score on their dashboard then updates.

### Word Match Game (`quiz/game.php`)

A fun drag-click game where students match tech terms to definitions. Fully client-side JavaScript. Saves score to quiz_results when finished.

---

## 14. How AI Recommendations Work

The file `api/recommendations.php` returns a JSON list of courses personalized for a student.

**It is called via JavaScript `fetch()` on the homepage and student dashboard.**

### The 5 Signals Used

```
Signal 1: Category Affinity (+50 points)
  "What categories is this student already studying?"
  → Look at enrollments → get all category_ids of enrolled courses
  → Any un-enrolled course in the same category gets +50 points

Signal 2: Quiz Activity (+20 points)
  "What topics has this student been tested on?"
  → Look at quiz_results → find quiz categories
  → Courses in those categories get +20 points

Signal 3: Level Progression (+30 points)
  "What's the next challenge for this student?"
  → If enrolled in Beginner courses → recommend Intermediate too
  → If enrolled in Intermediate → recommend Advanced too
  → Matching level gets +30 points

Signal 4: Popularity (+up to 30 points)
  "What are other students taking?"
  → enrollment_count × 2 (capped at 30)

Signal 5: Rating (+up to ~25 points)
  "What is highly rated?"
  → avg_rating × 5 (e.g. 5-star course = +25)
  → If rating ≥ 4.5 → label changes to "⭐ Top rated"
```

### Diversity Filter

After scoring, the algorithm ensures **no instructor appears more than twice** in the final list. This prevents one popular teacher from taking all 6 recommendation slots.

### Response Format (JSON)

```json
[
  {
    "id": 2,
    "title": "JavaScript Mastery",
    "slug": "javascript-mastery",
    "category": "Programming",
    "instructor": "Jaya Yadav",
    "price": 2999,
    "type": "premium",
    "level": "intermediate",
    "rating": 4.8,
    "enrolls": 234,
    "icon": "💻",
    "thumbnail": "https://i.ibb.co/...",
    "reason": "category_match",
    "reason_label": "📂 Matches your interests",
    "score": 87.4
  }
]
```

---

## 15. How the Frontend (CSS + JS) Works

### `assets/css/style.css`

One big CSS file that controls everything. Key sections:

```
CSS Variables (root)  → colors, fonts, spacing
Navbar                → fixed top bar, logo, links
Hero Section          → homepage big banner
Course Cards          → the grid of course thumbnails
Dashboard Layout      → sidebar + main content split
Sidebar               → left navigation panel
Stat Cards            → the colored number boxes
Forms                 → input fields, labels, buttons
Certificates          → verify page and generate page styles
Quizzes               → quiz question layout, option buttons
Footer                → teacher footer, admin footer, public footer
Responsive            → mobile adjustments (@media queries)
```

### CSS Custom Properties (Variables)

Defined in `:root {}` so you can change the whole color scheme in one place:

```css
--primary: #22c55e    /* Green — used everywhere */
--secondary: #3b82f6  /* Blue */
--bg-dark: #060b15    /* Page background */
--bg-card: #111827    /* Card backgrounds */
--border: rgba(255,255,255,0.07) /* Subtle borders */
--text-muted: #6b7280 /* Grey text */
```

### `assets/js/main.js`

Handles these things on every page:

| Function | What it does |
|---|---|
| `showToast(type, message)` | Shows a popup notification in the corner |
| Intersection Observer | Animates elements when they scroll into view |
| Counter animation | Counts up numbers (50K+ students etc.) |
| `liveSearch(query)` | Filters course cards by typing (no page reload) |
| `filterByTag(tag)` | Shows/hides course cards by type (free/premium) |
| `sortCourses(by)` | Re-orders course cards by price/rating |
| `setupStarRating()` | Makes star rating inputs interactive |
| `copyToClipboard(text)` | Copies certificate link to clipboard |
| Scroll effect | Navbar gets solid background when user scrolls down |

### `assets/js/quiz.js`

Runs entirely in the browser. No PHP once the page has loaded.
- Renders questions one at a time
- Highlights correct/wrong answers
- Adjusts difficulty adaptively
- Shows confetti on passing
- Sends result to server via `fetch()`

---

## 16. How Email (OTP) Works

The file `includes/mailer.php` uses **PHPMailer** to send emails via Gmail.

### Setup Requirements

1. A Gmail account
2. 2-Step Verification enabled on that Gmail account
3. An **App Password** generated from Google Account → Security → App Passwords

### What Emails Are Sent

| When | Email Type | Content |
|---|---|---|
| Registration | OTP Verification | 6-digit code, expires in 10 min |
| Login (new device) | OTP Verification | Same as above |
| OTP verified | Welcome Email | Welcome message with dashboard link |
| Course approved | (Notification only) | In-app notification, no email |

### OTP Flow in Detail

```
generateOTP() → 6-digit code (e.g. "847291")
  → saves to otp_codes table with expires_at = NOW() + 600 seconds
  → sends email via PHPMailer
  → user enters code in otp_verify.php
  → system checks: code matches + used=0 + expires_at > NOW()
  → marks used=1 (can never be used again)
  → sets user.is_verified=1
  → creates trusted_device entry + sets cookie
```

### SMTP Configuration (in `config/db.php`)

```php
define('SMTP_HOST',     'smtp.gmail.com');
define('SMTP_PORT',     587);           // TLS port
define('SMTP_USER',     'your@gmail.com');
define('SMTP_PASS',     'xxxx xxxx xxxx xxxx'); // App Password (NOT your real password!)
define('SMTP_FROM',     'your@gmail.com');
define('SMTP_FROM_NAME','EDUCORE');
```

---

## 17. How Google Login Works

The file `api/google_auth.php` handles the full OAuth 2.0 flow.

### Step-by-Step Flow

```
Step 1: User clicks "Sign in with Google"
  → Redirected to api/google_auth.php?action=redirect
  → PHP generates a random 'state' token (CSRF protection)
  → Saves state to $_SESSION
  → Redirects user to Google's login page

Step 2: User approves on Google
  → Google redirects back to api/google_auth.php?code=XYZ&state=ABC
  → PHP verifies state matches session (security check)

Step 3: Exchange code for access token
  → PHP sends code to Google's token endpoint
  → Gets back an access_token

Step 4: Get user profile
  → PHP calls Google's userinfo API with the access_token
  → Gets: id, name, email

Step 5: Create or find user
  → Check if email exists in users table
      ├── EXISTS → update google_id, log them in
      └── NEW → create account as 'student', is_verified=1 (Google already verified)

Step 6: Redirect to dashboard
```

### Configuration (in `.env` file)

```
GOOGLE_CLIENT_ID=xxxxx.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-xxxxx
```

Get these from: [console.cloud.google.com](https://console.cloud.google.com)
→ APIs & Services → Credentials → Create OAuth 2.0 Client ID
→ Add Authorized redirect URI: `http://localhost/edu-core/api/google_auth.php`

---

## 18. Clean URLs Explained

Without clean URLs, every link would look like:
```
http://localhost/edu-core/courses/detail.php?slug=javascript-mastery
```

With clean URLs it looks like:
```
http://localhost/edu-core/courses/javascript-mastery
```

Apache reads `.htaccess` and translates the clean URL to the real file internally.

### How to Add a New Clean URL

Open `.htaccess` and add a new `RewriteRule`:

```apache
# Maps 'profile' -> 'profile.php'
RewriteRule ^profile/?$ profile.php [L,QSA]

# Maps 'blog/[slug]' -> 'blog.php?slug=[slug]'  
RewriteRule ^blog/([^/.]+)/?$ blog.php?slug=$1 [L,QSA]
```

**Flags explained:**
- `L` = Last rule, stop processing more rules
- `QSA` = Keep existing query string parameters
- `[^/.]+` = Match anything except `/` and `.` (a URL slug)
- `$1` = The matched piece from the parentheses

---

## 19. Configuration — Every Setting

Everything is in `config/db.php`. Change these when deploying:

```php
// ── Database ─────────────────────────────────
define('DB_HOST', 'localhost');       // Usually 'localhost'
define('DB_USER', 'root');            // Your MySQL username
define('DB_PASS', '');                // Your MySQL password
define('DB_NAME', 'educore');         // Database name

// ── Site URL ─────────────────────────────────
define('BASE_URL', 'http://localhost/edu-core');
// On production: 'https://yourdomain.com'

// ── eSewa Payment ────────────────────────────
define('ESEWA_MERCHANT_CODE', 'EPAYTEST');  // Sandbox code
// On production: your real merchant code from eSewa

// ── OTP Settings ─────────────────────────────
define('OTP_EXPIRE_SECONDS', 600);    // 10 minutes
define('OTP_RESEND_COOLDOWN', 60);    // 1 minute between resends

// ── Email/SMTP ───────────────────────────────
define('SMTP_HOST',     'smtp.gmail.com');
define('SMTP_PORT',     587);
define('SMTP_USER',     'your@gmail.com');
define('SMTP_PASS',     'app password here');
define('SMTP_FROM',     'your@gmail.com');
define('SMTP_FROM_NAME','EDUCORE');
```

And in `.env` (never commit this to git!):
```
GOOGLE_CLIENT_ID=your_client_id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your_client_secret
```

---

## 20. How to Set Up From Scratch

Follow these steps exactly to get EDUCORE running on your computer.

### Prerequisites

- Install [XAMPP](https://www.apachefriends.org/) (gives you Apache + MySQL + PHP)
- Install [Composer](https://getcomposer.org/) (PHP package manager, needed for PHPMailer)

### Step 1 — Copy Files

Put the `edu-core` folder inside `C:\xampp\htdocs\`

```
C:\xampp\htdocs\edu-core\
```

### Step 2 — Start XAMPP

Open XAMPP Control Panel → Click **Start** on both:
- **Apache** (the web server)
- **MySQL** (the database)

### Step 3 — Create the Database

Open your browser → go to `http://localhost/phpmyadmin`

Click **SQL** tab, paste this and click Go:
```sql
CREATE DATABASE educore CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Then click on `educore` database → **Import** tab → choose the file:
```
C:\xampp\htdocs\edu-core\database\educore.sql
```
Click **Go**. This creates all tables and adds sample data.

### Step 4 — Install PHPMailer

Open Command Prompt in the `edu-core` folder:
```bash
cd C:\xampp\htdocs\edu-core
php composer.phar install
```

This creates the `vendor/` folder with PHPMailer.

### Step 5 — Configure the Site

Open `config/db.php` and update:
```php
define('DB_USER', 'root');   // your MySQL username (usually 'root' on XAMPP)
define('DB_PASS', '');       // your MySQL password (usually empty on XAMPP)
define('BASE_URL', 'http://localhost/edu-core');
```

Update SMTP settings with your Gmail App Password.

### Step 6 — Set Up Google Login (Optional)

1. Go to [console.cloud.google.com](https://console.cloud.google.com)
2. Create a new project
3. Go to **APIs & Services → Credentials**
4. Create **OAuth 2.0 Client ID** (Web application)
5. Add Authorized redirect URI: `http://localhost/edu-core/api/google_auth.php`
6. Copy the Client ID and Client Secret

Create/edit `.env` file in `edu-core/`:
```
GOOGLE_CLIENT_ID=xxxxx.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-xxxxx
```

### Step 7 — Run Migration (if updating)

Visit `http://localhost/edu-core/migrate.php` once.  
This adds any new database columns added since the initial setup (like `signature_image`).

### Step 8 — Open the Site

Go to: `http://localhost/edu-core/Homepage`

### Default Login Accounts (from seed data)

| Role | Email | Password |
|---|---|---|
| Admin | jy574018@gmail.com | password |
| Teacher | jaya@educore.com | password |
| Student | hari@educore.com | password |

> ⚠️ **Change these passwords immediately in production!**

---

## 🗺️ Complete Page Map

```
PUBLIC PAGES (no login needed)
├── /Homepage                     ← Landing page
├── /login.php                    ← Login
├── /register.php                 ← Register
├── /otp_verify.php               ← Email verification
├── /courses/                     ← Browse courses
├── /courses/[slug]               ← Course detail
├── /courses/type=free            ← Free courses
├── /quiz/                        ← Quiz selection
└── /certificates/verify/[code]  ← Verify a certificate

STUDENT PAGES (login required)
├── /dashboard/student            ← Student dashboard
├── /courses/enroll.php           ← Enrollment handler
├── /courses/learn.php            ← Lesson viewer
├── /payment/checkout.php         ← Payment page
├── /payment/success.php          ← Payment success
├── /quiz/?id=[n]                 ← Take a specific quiz
├── /quiz/game.php                ← Word match game
└── /certificates/generate/[slug] ← Get certificate

TEACHER PAGES (teacher/admin only)
└── /dashboard/teacher            ← Teacher dashboard

ADMIN PAGES (admin only)
└── /dashboard/admin              ← Admin panel

API ENDPOINTS (return JSON)
├── /api/recommendations.php      ← Course recommendations
├── /api/notifications.php        ← User notifications
├── /api/save_quiz_result.php     ← Save quiz score
├── /api/get_lesson.php           ← Lesson content
├── /api/upload_image.php         ← Image upload to ImgBB
└── /api/google_auth.php          ← Google OAuth callback
```

---

## 🔑 Key Concepts Summary (for beginners)

| Concept | Simple Explanation |
|---|---|
| **PHP** | Code that runs on the server. Mixes with HTML to build pages |
| **MySQL** | The database where all data is stored in tables |
| **Session** | Temporary storage in the server that remembers who is logged in |
| **Cookie** | Small data stored in the browser (used for trusted device) |
| **OTP** | One-Time Password — a 6-digit code sent by email |
| **Bcrypt** | A way to scramble passwords so they can't be read if stolen |
| **PDO** | The PHP library used to talk to MySQL (prevents SQL injection) |
| **SMTP** | The protocol used to send emails |
| **OAuth** | A way to let Google (or Facebook etc.) handle login for you |
| **REST API** | PHP files that return JSON instead of HTML (for JavaScript to fetch) |
| **mod_rewrite** | Apache feature that translates clean URLs to real file paths |
| **.htaccess** | Config file Apache reads for URL routing |
| **Bootstrap** | CSS framework that provides ready-made UI components |
| **Adaptive Quiz** | Quiz where difficulty changes based on how well you're doing |

---

*Built with ❤️ for EDUCORE — An open learning platform.*
