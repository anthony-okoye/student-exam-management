# Online Examination System - Test Suite

This directory contains comprehensive tests for the Online Examination System, including unit tests, integration tests, and security tests.

## Test Structure

```
tests/
├── bootstrap.php           # Test environment setup
├── Unit/                   # Unit tests for individual models
│   ├── UserModelTest.php
│   ├── QuestionModelTest.php
│   ├── ExamModelTest.php
│   └── ExamSessionModelTest.php
├── Integration/            # Integration tests for complete workflows
│   └── ExamWorkflowTest.php
└── Security/               # Security tests
    └── SecurityTest.php
```

## Requirements

- PHP 7.4 or higher
- PHPUnit 9.5 or higher
- MySQL/PostgreSQL database
- Composer

## Installation

1. Install dependencies:
```bash
composer install
```

2. Configure test database:
   - Create a test database (e.g., `hope_nurse_test`)
   - Update `phpunit.xml` with your test database credentials
   - The test database should have the same schema as your main database

3. Import database schema:
```bash
mysql -u root -p hope_nurse_test < database/schema.sql
```

## Running Tests

### Run all tests:
```bash
vendor/bin/phpunit
```

### Run specific test suite:
```bash
# Unit tests only
vendor/bin/phpunit --testsuite "Unit Tests"

# Integration tests only
vendor/bin/phpunit --testsuite "Integration Tests"

# Security tests only
vendor/bin/phpunit --testsuite "Security Tests"
```

### Run specific test file:
```bash
vendor/bin/phpunit tests/Unit/UserModelTest.php
```

### Run with verbose output:
```bash
vendor/bin/phpunit --verbose
```

### Run with code coverage (requires Xdebug):
```bash
vendor/bin/phpunit --coverage-html coverage/
```

## Test Coverage

### Unit Tests (tests/Unit/)

**UserModelTest.php** - Tests User model operations:
- User creation with password hashing
- User authentication and password verification
- Getting users by username and role
- Updating user information
- Deleting users
- Exam history retrieval

**QuestionModelTest.php** - Tests Question model operations:
- Creating questions of all types (multiple choice, true/false, fill blank, select all, short answer)
- Question type validation
- Getting questions by ID
- Deleting questions
- Option ordering

**ExamModelTest.php** - Tests Exam model operations:
- Exam creation, update, and deletion
- Question assignment to exams
- Duration calculation based on question count
- Student assignment to exams
- Retake prevention and allowance
- Cascading deletion

**ExamSessionModelTest.php** - Tests ExamSession model and scoring:
- Starting exam sessions
- Saving and updating answers
- Manual and auto-submission
- Scoring algorithms for all question types
- Case-insensitive and whitespace-trimmed scoring for fill-in-blank
- Percentage calculation
- Remaining time calculation
- Tab switch counting
- Completed exam immutability

### Integration Tests (tests/Integration/)

**ExamWorkflowTest.php** - Tests complete workflows:
- End-to-end exam creation to completion
- Partial scoring with mixed correct/incorrect answers
- Auto-submission workflow
- Retake prevention and allowance workflows
- Answer update workflow
- Tab switching during exams
- Cascading deletion

### Security Tests (tests/Security/)

**SecurityTest.php** - Tests security features:
- Password hashing with bcrypt
- Password verification
- SQL injection prevention in all queries
- XSS prevention with output escaping
- Input sanitization
- CSRF token generation and validation
- Timing attack prevention
- Session token security

## Test Database

The tests use a separate test database to avoid affecting production data. The database is automatically cleaned before and after each test to ensure test isolation.

### Helper Functions (bootstrap.php)

- `cleanupTestDatabase()` - Truncates all tables
- `createTestAdmin()` - Creates a test admin user
- `createTestStudent()` - Creates a test student user

## Requirements Validation

Each test includes comments indicating which requirements it validates. For example:

```php
/**
 * Test creating a new user
 * Validates: Requirements 4.1 - Admin creates student record
 */
public function testCreateUser() {
    // Test code...
}
```

This ensures traceability between tests and requirements.

## Test Principles

1. **Isolation**: Each test is independent and doesn't rely on other tests
2. **Cleanup**: Database is cleaned before and after each test
3. **Real Data**: Tests use real database operations, not mocks
4. **Comprehensive**: Tests cover happy paths, edge cases, and error conditions
5. **Security**: Dedicated tests for SQL injection, XSS, and CSRF protection

## Continuous Integration

These tests can be integrated into CI/CD pipelines:

```yaml
# Example GitHub Actions workflow
- name: Run tests
  run: |
    composer install
    vendor/bin/phpunit --coverage-text
```

## Troubleshooting

### Database connection errors
- Verify database credentials in `phpunit.xml`
- Ensure test database exists and is accessible
- Check that database schema is imported

### Test failures
- Check error messages for specific issues
- Verify database is clean (run `cleanupTestDatabase()`)
- Ensure all dependencies are installed

### Permission errors
- Verify database user has appropriate permissions
- Check file permissions on test files

## Contributing

When adding new features:
1. Write tests first (TDD approach)
2. Ensure tests cover all requirements
3. Add comments indicating which requirements are validated
4. Run all tests before committing
5. Maintain test isolation and cleanup

## Notes

- Tests use the same models and services as the main application
- No mocking is used to ensure real functionality is tested
- Security tests include actual SQL injection and XSS payloads
- Integration tests simulate complete user workflows
