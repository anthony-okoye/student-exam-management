# Online Examination System

A fully functional web-based examination platform that enables administrators to create and manage exams while allowing students to take timed assessments with automatic scoring and immediate results.

## Tech Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+ or PostgreSQL 12+ (including Supabase)
- **Frontend**: HTML5, CSS3, Bootstrap 5, Vanilla JavaScript/jQuery
- **Architecture**: MVC Pattern with PDO for database access

## Features Implemented

### ✅ Core MVP Features (Fully Functional)

#### Authentication & Authorization
- Secure user authentication with role-based access control
- Separate login contexts for Admin and Student roles
- Session management with protected routes
- Automatic redirect for unauthenticated users

#### Admin Features
- **Question Management**
  - Create, edit, and delete questions
  - Support for 3 question types:
    - Multiple Choice (2-4 options with single correct answer)
    - True/False (automatic 2-option generation)
    - Fill in the Blank (text-based answers)
  - Question bank with searchable list view
  - Dynamic form fields based on question type

- **Exam Management**
  - Create and manage exams with title, description, and settings
  - Assign questions from question bank to exams
  - Automatic duration calculation (2 minutes per question)
  - View all exams with status indicators
  - Comprehensive admin dashboard

- **Student Management**
  - Create, edit, and delete student accounts
  - View student exam history and scores
  - Assign exams to specific students
  - Control retake permissions
  - Comprehensive student list view

- **Analytics Dashboard**
  - Average scores per exam with visual charts
  - Pass/fail rates (60% threshold) with stacked bar charts
  - Question-level statistics showing difficulty (% correct)
  - Student performance trends over time with line charts
  - Overall system statistics (total exams, students, questions, sessions)
  - Interactive Chart.js visualizations
  - Detailed data tables for comprehensive analysis

#### Student Features
- **Dashboard**
  - View all available exams
  - Exam status tracking: "Not Started", "In Progress", "Completed"
  - Display scores for completed exams
  
- **Exam Taking**
  - Detailed exam instructions page before starting
  - Clean, distraction-free exam interface
  - Dynamic question rendering based on type:
    - Radio buttons for Multiple Choice
    - Radio buttons for True/False
    - Text input for Fill in the Blank
  - Real-time countdown timer (MM:SS format)
  - Auto-save answers on input change (debounced)
  - Manual submit button
  - Automatic submission when timer expires
  
- **Results & Feedback**
  - Immediate results after exam submission
  - Total score and percentage display
  - Pass/Fail status (60% threshold)
  - Question-by-question breakdown
  - Visual feedback (green for correct, red for incorrect)
  - Display of correct answers for learning

#### Scoring System
- Automatic scoring for all question types
- Multiple Choice: Exact match comparison
- True/False: Exact match comparison
- Fill in the Blank: Case-insensitive string comparison with whitespace trimming
- Persistent result storage

#### User Interface
- Fully responsive Bootstrap 5 design
- Mobile-friendly layouts
- Consistent navigation bars (role-specific)
- Professional color scheme and typography
- Loading spinners for AJAX operations
- Success/error message alerts
- Accessible form controls

### ✅ Enhanced Features (Fully Implemented)

#### Security Enhancements
- **Password Security**: bcrypt password hashing for all user accounts
- **SQL Injection Prevention**: PDO prepared statements for all database queries
- **XSS Protection**: htmlspecialchars() for all user output
- **CSRF Protection**: Token generation and validation for all forms
- **Session Security**: Secure session management with proper invalidation

#### Advanced Exam Features
- **Server-Side Timer Validation**: 
  - Server-calculated remaining time
  - Auto-submit on server-side time expiry
  - Timer synchronization every 30 seconds
  - Protection against client-side manipulation

- **State Persistence**:
  - Restore exam state on page refresh
  - Recover saved answers
  - Resume timer with server-calculated time
  - Maintain question order consistency

- **Anti-Cheating Measures**:
  - Tab switch detection using Visibility API
  - Event logging to database
  - Warning display on exam instructions
  - Tab switch count in results
  - Flagging for sessions with 3+ switches

#### Additional Question Types
- **Select All That Apply**: Multiple correct answers with checkbox inputs
- **Short Answer**: Open-ended text responses with textarea input
- Enhanced scoring logic for all question types

### 🔄 Planned Enhancements

The following features would be added with additional development time:

- **Comprehensive Testing**: Unit tests, integration tests, security tests, property-based tests
- **API Documentation**: Detailed API endpoint documentation
- **Advanced Analytics**: Date range filtering, export to PDF/CSV, predictive analytics
- **Email Notifications**: Exam assignment notifications, result notifications
- **Question Categories**: Organize questions by topic or subject
- **Exam Templates**: Reusable exam configurations
- **Bulk Operations**: Import/export questions and students via CSV

## Setup Instructions

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7+ **OR** PostgreSQL 12+ (including Supabase)
- Web server (Apache/Nginx) or PHP built-in server

> **Note**: For PostgreSQL/Supabase setup, see [POSTGRESQL_SETUP.md](POSTGRESQL_SETUP.md)

### Quick Setup (Automated) - MySQL

1. **Clone or download the project**
   ```bash
   cd online-examination-system
   ```

2. **Configure environment variables**
   ```bash
   # Copy the example environment file
   cp .env.example .env
   
   # Edit .env with your database credentials
   # For MySQL (default):
   # DB_CONNECTION=mysql
   # DB_HOST=localhost
   # DB_PORT=3306
   # DB_NAME=online_exam_system
   # DB_USER=root
   # DB_PASS=
   ```

3. **Run the automated installer**
   ```bash
   php database/install.php
   ```
   
   The installer will:
   - Check for .env file (creates from .env.example if missing)
   - Create the database
   - Import the schema
   - Seed test data (admin and student accounts)
   - Verify the setup

4. **Start the development server**
   ```bash
   cd public
   php -S localhost:8000
   ```

5. **Access the application**
   - Open browser: `http://localhost:8000`
   - Login with test credentials (see below)

### Quick Setup (Automated) - PostgreSQL/Supabase

1. **Clone or download the project**
   ```bash
   cd online-examination-system
   ```

2. **Configure environment variables**
   ```bash
   # Copy the example environment file
   cp .env.example .env
   
   # Edit .env with your PostgreSQL/Supabase credentials
   # DB_CONNECTION=pgsql
   # DB_HOST=db.adyrjuoqnznfzpmkvxee.supabase.co
   # DB_PORT=5432
   # DB_NAME=postgres
   # DB_USER=postgres
   # DB_PASS=your-supabase-password
   ```

3. **Run the PostgreSQL installer**
   ```bash
   php database/install_postgresql.php
   ```

4. **Start the development server**
   ```bash
   cd public
   php -S localhost:8000
   ```

5. **Access the application**
   - Open browser: `http://localhost:8000`
   - Login with test credentials (see below)

> **Detailed PostgreSQL/Supabase Guide**: See [POSTGRESQL_SETUP.md](POSTGRESQL_SETUP.md) for complete instructions

### Manual Setup (Alternative)

1. **Clone or download the project**
   ```bash
   cd online-examination-system
   ```

2. **Configure environment variables**
   ```bash
   # Copy the example environment file
   cp .env.example .env
   ```
   
   Edit `.env` file with your settings:
   ```env
   DB_HOST=localhost
   DB_NAME=online_exam_system
   DB_USER=root
   DB_PASS=your_password_here
   DB_CHARSET=utf8mb4
   ```

3. **Create database**
   ```bash
   mysql -u root -p -e "CREATE DATABASE online_exam_system;"
   ```

4. **Import database schema**
   ```bash
   mysql -u root -p online_exam_system < database/schema.sql
   ```

5. **Seed initial data**
   ```bash
   mysql -u root -p online_exam_system < database/seed.sql
   ```

6. **Start the development server**
   ```bash
   cd public
   php -S localhost:8000
   ```

7. **Access the application**
   - Open browser: `http://localhost:8000`

## Test Credentials

### Admin Account
- **Username**: `admin`
- **Password**: `admin123`
- **Access**: Full system administration
  - Create/edit/delete questions
  - Create/edit/delete exams
  - Assign questions to exams
  - Manage student accounts
  - Assign exams to students
  - View analytics dashboard with performance metrics
  - View all exam sessions and results

### Student Accounts
- **Student 1**:
  - Username: `student1`
  - Password: `pass123`
  
- **Student 2**:
  - Username: `student2`
  - Password: `pass123`

**Note**: All students can currently access all exams (assignment logic is planned for future enhancement).

## Sample Data Included

After running the installer, the database will be populated with:

### Users
- 1 Admin account
- 2 Student accounts

### Questions (9 total)
**Multiple Choice (3 questions)**:
- "What is 2 + 2?" (Answer: 4)
- "Which programming language is this system built with?" (Answer: PHP)
- "What does MVC stand for?" (Answer: Model View Controller)

**True/False (3 questions)**:
- "PHP is a server-side programming language." (Answer: True)
- "MySQL is a NoSQL database." (Answer: False)
- "Bootstrap is a CSS framework." (Answer: True)

**Fill in the Blank (3 questions)**:
- "The capital of France is ____." (Answer: Paris)
- "HTML stands for HyperText ____ Language." (Answer: Markup)
- "The default port for HTTP is ____." (Answer: 80)

### Exams (3 ready-to-take)
1. **Sample Programming Quiz** (6 minutes, 3 questions)
   - One question of each type
   - Perfect for quick testing

2. **Web Development Basics** (12 minutes, 6 questions)
   - Mix of all question types
   - Tests web development knowledge

3. **Complete Assessment** (18 minutes, 9 questions)
   - All 9 questions included
   - Comprehensive test

**All exams are ready to take immediately after installation!**

## Project Structure

```
/online-examination-system
├── /config                      # Configuration files
│   ├── database.php            # Database connection (PDO)
│   └── constants.php           # Application constants
├── /controllers                 # Application controllers (MVC)
│   ├── AuthController.php      # Authentication logic
│   ├── AdminController.php     # Admin operations (questions, exams)
│   ├── ExamController.php      # Exam operations (start, submit, save)
│   └── StudentController.php   # Student operations (dashboard, results)
├── /models                      # Data models (MVC)
│   ├── User.php                # User model
│   ├── Question.php            # Question model
│   ├── Exam.php                # Exam model
│   └── ExamSession.php         # Exam session model
├── /middleware                  # Middleware components
│   └── auth.php                # Authentication middleware
├── /views                       # View templates (MVC)
│   ├── /admin                  # Admin views
│   │   ├── dashboard.php       # Admin home
│   │   ├── questions.php       # Question management
│   │   └── exams.php           # Exam management
│   ├── /student                # Student views
│   │   ├── dashboard.php       # Student home
│   │   ├── exam-instructions.php
│   │   ├── exam-taking.php     # Exam interface
│   │   └── results.php         # Results display
│   └── /auth                   # Authentication views
│       └── login.php           # Login form
├── /public                      # Public assets and entry point
│   ├── /css
│   │   └── styles.css          # Custom styles
│   ├── /js
│   │   ├── timer.js            # Exam timer logic
│   │   ├── exam.js             # Exam taking logic
│   │   └── admin.js            # Admin interface logic
│   └── index.php               # Application entry point
├── /database                    # Database files
│   ├── schema.sql              # Database schema
│   ├── seed.sql                # Sample data
│   └── install.php             # Automated installer
├── .env                         # Environment variables (not in git)
├── .env.example                 # Environment template
├── .gitignore                   # Git ignore rules
└── README.md                    # This file
```

## Usage Guide

### For Administrators

1. **Login** with admin credentials
2. **Create Questions**:
   - Navigate to "Manage Questions"
   - Click "Add New Question"
   - Select question type and fill in details
   - For Multiple Choice: Add 2-4 options and mark the correct one
   - For True/False: System auto-creates True/False options
   - For Fill in Blank: Enter the correct answer text
3. **Create Exams**:
   - Navigate to "Manage Exams"
   - Click "Create New Exam"
   - Enter exam title and description
   - Select questions from the question bank
   - Duration is calculated automatically (2 min per question)
4. **View Results**:
   - All exam sessions are stored in the database
   - View student performance through exam sessions table

### For Students

1. **Login** with student credentials
2. **View Available Exams**:
   - Dashboard shows all exams with status
   - "Not Started" - Exam not yet attempted
   - "In Progress" - Exam currently active
   - "Completed" - Shows score and percentage
3. **Take an Exam**:
   - Click "View Details" on any exam
   - Read instructions carefully
   - Click "Start Exam" to begin
   - Answer questions (auto-saved)
   - Watch the timer countdown
   - Submit manually or wait for auto-submit
4. **View Results**:
   - Immediate results after submission
   - See total score and percentage
   - Review each question with correct answers
   - Green highlights = correct, Red highlights = incorrect

## Testing the Complete Workflow

### End-to-End Test Scenario

1. **Admin Creates Content**:
   ```
   Login as admin (admin/admin123)
   → Create 3 questions (1 Multiple Choice, 1 True/False, 1 Fill in Blank)
   → Create an exam and assign all 3 questions
   → Logout
   ```

2. **Student Takes Exam**:
   ```
   Login as student1 (student1/pass123)
   → View exam on dashboard
   → Click "View Details" → "Start Exam"
   → Answer all questions
   → Submit exam (or wait for timer)
   → View results
   → Logout
   ```

3. **Verify Results**:
   ```
   Login as student1 again
   → Dashboard shows "Completed" status with score
   → Click "View Results" to see detailed feedback
   ```

### Sample Test Data

After running the seed script, you can create:

**Sample Multiple Choice Question**:
- Question: "What is 2 + 2?"
- Options: "3", "4", "5", "6"
- Correct: "4"

**Sample True/False Question**:
- Question: "PHP is a server-side language."
- Correct: "True"

**Sample Fill in Blank Question**:
- Question: "The capital of France is ____."
- Correct Answer: "Paris"

## What I Would Add With More Time

### Security Enhancements (High Priority)
- **Password Security**: Implement bcrypt hashing for all passwords (currently using basic comparison for MVP)
- **SQL Injection Prevention**: Add prepared statements throughout (partially implemented)
- **XSS Protection**: Implement htmlspecialchars() for all user output
- **CSRF Protection**: Add token generation and validation for all forms
- **Session Security**: Implement secure session tokens with expiration
- **Input Validation**: Comprehensive server-side validation for all inputs

### Timer & State Management
- **Server-Side Timer**: Store start time in database and calculate remaining time server-side
- **Timer Synchronization**: Sync client timer with server every 30 seconds
- **Auto-Submit Validation**: Server-side validation to prevent timer manipulation
- **State Persistence**: Restore exam state (answers, time) on page refresh
- **Resume Capability**: Allow students to resume in-progress exams

### Additional Features
- **More Question Types**: 
  - Select All That Apply (multiple correct answers)
  - Short Answer (manual or keyword-based grading)
- **Student Management UI**: Admin interface to create/edit/delete students
- **Exam Assignment Logic**: Assign specific exams to specific students
- **Retake Controls**: Configure whether students can retake exams
- **Question Categories**: Organize questions by subject/topic
- **Question Difficulty**: Tag questions with difficulty levels
- **Randomization**: Randomize question and option order

### Anti-Cheating Measures
- **Tab Switch Detection**: Log when students switch browser tabs
- **Flagging System**: Flag sessions with suspicious behavior
- **Warning Display**: Show monitoring notice on exam instructions
- **Activity Logging**: Comprehensive audit trail of all exam actions

### Analytics & Reporting
- **Performance Dashboard**: Average scores, pass/fail rates
- **Question Analytics**: Percentage correct per question
- **Student Trends**: Performance over time
- **Export Functionality**: Export results to CSV/PDF
- **Visualizations**: Charts and graphs using Chart.js

### Testing & Quality Assurance
- **Unit Tests**: PHPUnit tests for all models and controllers
- **Integration Tests**: Test complete workflows
- **Security Tests**: Test for SQL injection, XSS vulnerabilities
- **Performance Tests**: Load testing for concurrent users
- **Code Coverage**: Aim for 80%+ test coverage

### Deployment & DevOps
- **Environment Configuration**: Separate dev/staging/production configs
- **Docker Support**: Containerization for easy deployment
- **CI/CD Pipeline**: Automated testing and deployment
- **Database Migrations**: Version-controlled schema changes
- **Logging System**: Comprehensive application logging
- **Error Handling**: User-friendly error pages

### User Experience
- **Email Notifications**: Notify students of exam assignments and results
- **Exam Scheduling**: Set start and end dates for exams
- **Practice Mode**: Allow students to practice without recording scores
- **Accessibility**: WCAG 2.1 AA compliance
- **Internationalization**: Multi-language support
- **Dark Mode**: Theme switching capability

## Technical Decisions & Rationale

### Why PHP & MySQL?
- Widely supported hosting environment
- Low barrier to entry for deployment
- Mature ecosystem with extensive documentation
- PDO provides secure database abstraction

### Why MVC Pattern?
- Clear separation of concerns
- Easier to maintain and test
- Scalable architecture
- Industry-standard approach

### Why Client-Side Timer for MVP?
- Faster implementation for demonstration
- Acceptable for controlled environments
- Server-side validation planned for production

### Why Bootstrap?
- Rapid UI development
- Mobile-responsive out of the box
- Professional appearance
- Extensive component library

## Known Limitations (MVP)

1. **Security**: Basic authentication without bcrypt (add for production)
2. **Timer**: Client-side only (add server validation for production)
3. **State**: No page refresh persistence (add for production)
4. **Assignment**: All students see all exams (add assignment logic for production)
5. **Validation**: Limited input validation (enhance for production)

## Database Schema Highlights

- **users**: Stores admin and student accounts
- **questions**: Stores all question types with content
- **question_options**: Stores options for choice-based questions
- **exams**: Stores exam metadata and settings
- **exam_questions**: Junction table linking exams to questions
- **exam_sessions**: Tracks student exam attempts
- **answers**: Stores student answers with scoring

## Performance Considerations

- PDO prepared statements for database queries
- AJAX for auto-save to prevent page reloads
- Debounced input handlers to reduce server requests
- Indexed database columns for faster queries
- Session-based authentication (no database hit per request)

## Browser Compatibility

- Chrome 90+ ✅
- Firefox 88+ ✅
- Safari 14+ ✅
- Edge 90+ ✅

## Contributing

This is an educational project. Suggestions and improvements are welcome!

## License

This project is for educational and demonstration purposes.

---

**Built with ❤️ using PHP, MySQL, Bootstrap, and JavaScript**
