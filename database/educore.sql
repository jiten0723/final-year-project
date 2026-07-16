-- ============================================
-- EDUCORE - E-Learning Platform Database
-- ============================================

CREATE DATABASE IF NOT EXISTS educore CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE educore;

-- ============================================
-- TABLE: categories
-- ============================================
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    icon VARCHAR(50) DEFAULT 'fas fa-book',
    color VARCHAR(20) DEFAULT '#3B82F6',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- TABLE: users
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','teacher','student') DEFAULT 'student',
    avatar VARCHAR(255) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    is_verified TINYINT(1) DEFAULT 1,
    google_id VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- TABLE: courses
-- ============================================
CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    short_description VARCHAR(500),
    category_id INT,
    instructor_id INT NOT NULL,
    thumbnail VARCHAR(255) DEFAULT NULL,
    preview_video VARCHAR(255) DEFAULT NULL,
    price DECIMAL(10,2) DEFAULT 0.00,
    type ENUM('free','premium') DEFAULT 'free',
    level ENUM('beginner','intermediate','advanced') DEFAULT 'beginner',
    duration VARCHAR(50) DEFAULT NULL,
    total_lessons INT DEFAULT 0,
    status ENUM('pending','approved','rejected','draft') DEFAULT 'pending',
    is_featured TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================
-- TABLE: lessons
-- ============================================
CREATE TABLE IF NOT EXISTS lessons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content LONGTEXT,
    video_url VARCHAR(500) DEFAULT NULL,
    duration_minutes INT DEFAULT 0,
    order_num INT DEFAULT 1,
    is_free_preview TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- ============================================
-- TABLE: enrollments
-- ============================================
CREATE TABLE IF NOT EXISTS enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    status ENUM('active','completed','cancelled') DEFAULT 'active',
    progress INT DEFAULT 0,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    UNIQUE KEY unique_enrollment (user_id, course_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- ============================================
-- TABLE: lesson_progress
-- ============================================
CREATE TABLE IF NOT EXISTS lesson_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    lesson_id INT NOT NULL,
    completed TINYINT(1) DEFAULT 0,
    completed_at TIMESTAMP NULL,
    UNIQUE KEY unique_progress (user_id, lesson_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
);

-- ============================================
-- TABLE: payments
-- ============================================
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    method ENUM('esewa','paypal','free') DEFAULT 'free',
    transaction_id VARCHAR(255) DEFAULT NULL,
    status ENUM('pending','completed','failed','refunded') DEFAULT 'pending',
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
); 

-- ============================================
-- TABLE: quizzes
-- ============================================
CREATE TABLE IF NOT EXISTS quizzes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    total_questions INT DEFAULT 0,
    pass_percentage INT DEFAULT 60,
    is_adaptive TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL
);

-- ============================================
-- TABLE: quiz_questions
-- ============================================
CREATE TABLE IF NOT EXISTS quiz_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    question TEXT NOT NULL,
    option_a VARCHAR(500) NOT NULL,
    option_b VARCHAR(500) NOT NULL,
    option_c VARCHAR(500) NOT NULL,
    option_d VARCHAR(500) NOT NULL,
    correct_option CHAR(1) NOT NULL,
    difficulty ENUM('easy','medium','hard') DEFAULT 'easy',
    explanation TEXT DEFAULT NULL,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);

-- ============================================
-- TABLE: quiz_results
-- ============================================
CREATE TABLE IF NOT EXISTS quiz_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    quiz_id INT NOT NULL,
    score INT DEFAULT 0,
    total_questions INT DEFAULT 0,
    percentage DECIMAL(5,2) DEFAULT 0,
    passed TINYINT(1) DEFAULT 0,
    taken_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);

-- ============================================
-- TABLE: reviews
-- ============================================
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    review TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_review (user_id, course_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- ============================================
-- TABLE: certificates
-- ============================================
CREATE TABLE IF NOT EXISTS certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    certificate_code VARCHAR(100) NOT NULL UNIQUE,
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- ============================================
-- TABLE: notifications
-- ============================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) DEFAULT 'info',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================
-- TABLE: trusted_devices (cookie-based 2FA skip)
-- ============================================
CREATE TABLE IF NOT EXISTS trusted_devices (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    token       VARCHAR(64) NOT NULL UNIQUE,
    user_agent  VARCHAR(255) DEFAULT NULL,
    ip_address  VARCHAR(45) DEFAULT NULL,
    expires_at  DATETIME NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================
-- TABLE: otp_codes
-- ============================================
CREATE TABLE IF NOT EXISTS otp_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    code VARCHAR(10) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================
-- SEED DATA
-- ============================================

-- Categories
INSERT INTO categories (name, slug, icon, color) VALUES
('Programming', 'programming', 'fas fa-code', '#3B82F6'),
('Design', 'design', 'fas fa-paint-brush', '#8B5CF6'),
('Business', 'business', 'fas fa-briefcase', '#10B981'),
('Music', 'music', 'fas fa-music', '#F59E0B'),
('Photography', 'photography', 'fas fa-camera', '#EF4444'),
('Marketing', 'marketing', 'fas fa-bullhorn', '#EC4899'),
('Data Science', 'data-science', 'fas fa-chart-bar', '#06B6D4'),
('Personal Dev', 'personal-dev', 'fas fa-user-graduate', '#84CC16');

-- Users (passwords are bcrypt hash of "password123")
INSERT INTO users (name, email, password, role, bio) VALUES
('Admin User', 'jy574018@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Platform Administrator'),
('Jaya Yadav', 'jaya@educore.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'Full-stack developer with 10+ years of experience. Passionate about teaching modern web technologies.'),
('Hari KC', 'hari@educore.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Aspiring developer learning web technologies'),
('Shyam Bk', 'shyam@educore.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'UI/UX Designer and Figma expert with 8 years in product design.'),
('Uday Limbu', 'uday@educore.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Business student exploring digital marketing');

-- Courses
INSERT INTO courses (title, slug, description, short_description, category_id, instructor_id, price, type, level, duration, total_lessons, status, is_featured) VALUES
('Complete HTML & CSS Bootcamp', 'complete-html-css-bootcamp', 'Learn HTML5 and CSS3 from scratch to build modern, responsive websites. This comprehensive course covers everything from basic tags to advanced flexbox and grid layouts, animations, and responsive design principles.', 'Master HTML5 and CSS3 to build stunning websites from scratch.', 1, 2, 0.00, 'free', 'beginner', '12 hours', 24, 'approved', 1),
('JavaScript Mastery: Zero to Hero', 'javascript-mastery', 'Deep dive into modern JavaScript ES6+. Learn closures, promises, async/await, DOM manipulation, and build real-world projects including a weather app, todo list, and e-commerce cart.', 'Master modern JavaScript with hands-on projects.', 1, 2, 2999.00, 'premium', 'intermediate', '28 hours', 56, 'approved', 1),
('UI/UX Design with Figma', 'ui-ux-design-figma', 'Learn professional UI/UX design using Figma. Create wireframes, prototypes, and design systems. Understand user research, accessibility, and modern design trends used by top tech companies.', 'Create stunning designs using Figma like a professional.', 2, 4, 1999.00, 'premium', 'beginner', '18 hours', 36, 'approved', 1),
('Python for Data Science', 'python-data-science', 'Learn Python programming with a focus on data science. Cover NumPy, Pandas, Matplotlib, machine learning basics with scikit-learn, and work with real datasets to build your portfolio.', 'Master Python for data analysis and machine learning.', 7, 2, 3499.00, 'premium', 'intermediate', '35 hours', 70, 'approved', 1),
('Digital Marketing Fundamentals', 'digital-marketing-fundamentals', 'Understand the complete digital marketing ecosystem: SEO, SEM, social media marketing, email campaigns, content strategy, and analytics. Learn to create and execute campaigns that drive real results.', 'Learn digital marketing strategies to grow any business online.', 6, 4, 0.00, 'free', 'beginner', '10 hours', 20, 'approved', 0),
('React.js Complete Guide', 'reactjs-complete-guide', 'Build modern web applications with React.js. Learn hooks, context API, Redux, Next.js basics, and deploy production apps. Create 5 real projects including a social media clone.', 'Build production-ready apps with React.js and modern tools.', 1, 2, 4999.00, 'premium', 'advanced', '42 hours', 84, 'approved', 1);

-- Lessons for Course 1
INSERT INTO lessons (course_id, title, content, duration_minutes, order_num, is_free_preview) VALUES
(1, 'Introduction to HTML', 'HTML (HyperText Markup Language) is the backbone of every webpage. In this lesson, we will cover the basic structure of an HTML document, including the DOCTYPE declaration, html, head, and body tags.\n\n**What you will learn:**\n- HTML document structure\n- Basic tags: headings, paragraphs, links\n- How browsers render HTML\n\nHTML was created by Tim Berners-Lee in 1991 and has evolved through HTML5, which is the current standard. Every web page you visit is built with HTML at its core.', 15, 1, 1),
(1, 'HTML Elements & Attributes', 'In this lesson, we explore HTML elements and their attributes. Every HTML element can have attributes that provide additional information.\n\n**Core Elements:**\n- Headings (h1-h6)\n- Paragraphs (p)\n- Links (a href)\n- Images (img src, alt)\n- Lists (ul, ol, li)\n\nAttributes are always specified in the opening tag and usually come in name/value pairs.', 20, 2, 1),
(1, 'CSS Fundamentals', 'CSS (Cascading Style Sheets) controls the visual presentation of HTML elements. Learn selectors, properties, and the cascade.\n\n**Topics Covered:**\n- CSS selectors (element, class, id)\n- Box model (margin, padding, border)\n- Colors and typography\n- Display property\n\nCSS makes websites beautiful and provides the tools to create layouts, animations, and responsive designs.', 25, 3, 0),
(1, 'Flexbox Layout', 'Master CSS Flexbox - the modern way to create flexible and responsive layouts without floats or positioning hacks.\n\n**Flexbox Properties:**\n- display: flex\n- flex-direction\n- justify-content\n- align-items\n- flex-wrap\n\nFlexbox solves many common layout challenges and is supported in all modern browsers.', 30, 4, 0);

-- Lessons for Course 2
INSERT INTO lessons (course_id, title, content, duration_minutes, order_num, is_free_preview) VALUES
(2, 'JavaScript Introduction', 'JavaScript is the programming language of the web. Learn variables, data types, and your first script.\n\n**Variables:**\n- let, const, var\n- Data types: string, number, boolean, null, undefined\n- Type coercion\n\nJavaScript runs in the browser and on servers (Node.js), making it the most versatile programming language today.', 20, 1, 1),
(2, 'Functions & Scope', 'Understand how functions work in JavaScript and the crucial concept of scope.\n\n**Topics:**\n- Function declarations vs expressions\n- Arrow functions\n- Scope (global, function, block)\n- Closures\n\nClosures are one of the most powerful features in JavaScript, enabling patterns like module pattern and factory functions.', 35, 2, 0),
(2, 'DOM Manipulation', 'Learn how to interact with HTML elements using JavaScript to create dynamic, interactive webpages.\n\n**Key Methods:**\n- querySelector / querySelectorAll\n- addEventListener\n- createElement, appendChild\n- classList manipulation\n\nDOM manipulation is the foundation of all interactive web experiences.', 40, 3, 0);

-- Quizzes
INSERT INTO quizzes (course_id, title, description, total_questions, pass_percentage, is_adaptive) VALUES
(1, 'HTML & CSS Basics Quiz', 'Test your knowledge of HTML5 and CSS3 fundamentals', 5, 60, 0),
(2, 'JavaScript Fundamentals Quiz', 'Adaptive quiz testing JavaScript knowledge at multiple difficulty levels', 6, 70, 1),
(NULL, 'General Web Development Quiz', 'Test your overall web development knowledge', 5, 60, 0);

-- Quiz Questions (Quiz 1 - HTML/CSS)
INSERT INTO quiz_questions (quiz_id, question, option_a, option_b, option_c, option_d, correct_option, difficulty, explanation) VALUES
(1, 'What does HTML stand for?', 'Hyper Text Markup Language', 'High Tech Modern Language', 'Hyper Transfer Markup Language', 'Home Tool Markup Language', 'A', 'easy', 'HTML stands for Hyper Text Markup Language, the standard language for creating web pages.'),
(1, 'Which tag is used for the largest heading in HTML?', '&lt;h6&gt;', '&lt;heading&gt;', '&lt;h1&gt;', '&lt;head&gt;', 'C', 'easy', '&lt;h1&gt; defines the largest heading. HTML has six levels of headings, &lt;h1&gt; through &lt;h6&gt;.'),
(1, 'Which CSS property controls text size?', 'text-size', 'font-size', 'text-style', 'font-style', 'B', 'easy', 'The font-size property specifies the size of the font.'),
(1, 'What does CSS stand for?', 'Computer Style Sheets', 'Creative Style System', 'Cascading Style Sheets', 'Colorful Style Sheets', 'C', 'easy', 'CSS stands for Cascading Style Sheets, used to style HTML elements.'),
(1, 'Which property is used in CSS Flexbox to align items along the main axis?', 'align-items', 'justify-content', 'flex-align', 'main-align', 'B', 'medium', 'justify-content aligns flex items along the main axis (horizontal by default).');

-- Quiz Questions (Quiz 2 - JavaScript)
INSERT INTO quiz_questions (quiz_id, question, option_a, option_b, option_c, option_d, correct_option, difficulty, explanation) VALUES
(2, 'Which keyword declares a block-scoped variable in modern JavaScript?', 'var', 'let', 'define', 'variable', 'B', 'easy', 'let declares a block-scoped variable, unlike var which is function-scoped.'),
(2, 'What does === mean in JavaScript?', 'Assignment', 'Loose equality', 'Strict equality', 'Greater than', 'C', 'easy', '=== checks both value and type without type coercion.'),
(2, 'What is a closure in JavaScript?', 'A loop construct', 'A function with access to its outer scope', 'A way to close browser tabs', 'An error handling mechanism', 'B', 'medium', 'A closure is a function that remembers variables from its outer lexical scope even after the outer function has returned.'),
(2, 'Which method converts a JSON string to a JavaScript object?', 'JSON.stringify()', 'JSON.parse()', 'JSON.convert()', 'JSON.decode()', 'B', 'medium', 'JSON.parse() parses a JSON string and returns a JavaScript object.'),
(2, 'What does the "async" keyword do before a function?', 'Makes it run faster', 'Makes it return a Promise', 'Makes it synchronous', 'Adds event listener', 'B', 'hard', 'Async functions automatically return a Promise and allow use of await inside them.'),
(2, 'Which Array method creates a new array with elements that pass a test?', 'map()', 'reduce()', 'filter()', 'find()', 'C', 'hard', 'filter() creates a new array with all elements that pass the test implemented by the provided function.');

-- Quiz Questions (Quiz 3 - General)
INSERT INTO quiz_questions (quiz_id, question, option_a, option_b, option_c, option_d, correct_option, difficulty, explanation) VALUES
(3, 'What does API stand for?', 'Application Programming Interface', 'Application Process Integration', 'Advanced Programming Index', 'Automated Protocol Interface', 'A', 'easy', 'API stands for Application Programming Interface - a set of rules for how applications communicate.'),
(3, 'Which HTTP method is used to submit form data?', 'GET', 'PUT', 'POST', 'DELETE', 'C', 'easy', 'POST is typically used to send form data to a server.'),
(3, 'What is responsive web design?', 'Fast loading pages', 'Design that adapts to different screen sizes', 'Pages with animations', 'Server-side rendering', 'B', 'easy', 'Responsive design ensures websites look and work well on all devices and screen sizes.'),
(3, 'Which of these is a version control system?', 'MySQL', 'PHP', 'Git', 'Apache', 'C', 'medium', 'Git is a distributed version control system for tracking changes in source code.'),
(3, 'What does SQL stand for?', 'Simple Query Language', 'Structured Query Language', 'System Query Logic', 'Standard Question Language', 'B', 'easy', 'SQL stands for Structured Query Language, used to manage relational databases.');

-- Enrollments (student enrolled in free courses)
INSERT INTO enrollments (user_id, course_id, status, progress) VALUES
(3, 1, 'active', 50),
(3, 5, 'active', 25),
(5, 1, 'active', 75),
(5, 3, 'active', 30);

-- Reviews
INSERT INTO reviews (user_id, course_id, rating, review) VALUES
(3, 1, 5, 'Absolutely excellent course! John explains everything so clearly. I went from zero to building my own websites in just 2 weeks. Highly recommended for beginners!'),
(5, 1, 4, 'Great course content and well structured. The flexbox section was especially helpful. Would love more real-world project examples.'),
(3, 5, 5, 'Best digital marketing course I have found. Sarah covers everything from SEO to social media in a very practical way. Perfect for entrepreneurs!');

-- Notifications
INSERT INTO notifications (user_id, message, type) VALUES
(3, 'Welcome to EDUCORE! Start your learning journey today.', 'success'),
(3, 'You have been enrolled in "Complete HTML & CSS Bootcamp". Good luck!', 'info'),
(2, 'Your course "JavaScript Mastery" has been approved by the admin!', 'success'),
(2, 'A new student enrolled in your course "Complete HTML & CSS Bootcamp".', 'info');
