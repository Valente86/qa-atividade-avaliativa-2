# Integration Tests Setup and Documentation

## Overview

This document provides comprehensive information about the integration test suite for the Biblioteca Management Application. The test suite covers all primary API endpoints and business logic scenarios.

## Test Coverage Summary

### Test Files Created

1. **BibliotecasControllerTest.php** - Library Management Tests
   - 12 test methods
   - Covers: index, create, store, edit, update, destroy

2. **UserControllerTest.php** - User Management Tests
   - 14 test methods
   - Covers: index, show, create, store, edit, update, destroy

3. **PessoaControllerTest.php** - Person Management Tests
   - 11 test methods
   - Covers: index, create, store, edit, update, password handling, destroy

4. **BibliotecaPessoaControllerTest.php** - Library-Person Association Tests
   - 10 test methods
   - Covers: create (form display), store (linking), error scenarios

**Total: 47 integration test methods**

## Running Tests Locally

### Prerequisites
```bash
# Install PHP dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Create SQLite database for testing
touch database/testing.sqlite
```

### Running All Tests
```bash
# Run all tests
php artisan test

# Run only Feature tests
php artisan test --testsuite=Feature

# Run with coverage report
php artisan test --coverage

# Run specific test file
php artisan test tests/Feature/BibliotecasControllerTest.php

# Run specific test method
php artisan test tests/Feature/BibliotecasControllerTest.php --filter test_index_returns_view_with_all_bibliotecas
```

### Test Database Setup

Tests use SQLite in-memory database for speed and isolation:

```php
// phpunit.xml configuration
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

## Test Scenarios Covered

### BibliotecasController (Library Management)

#### READ Operations
- ✅ List all libraries without filters
- ✅ Search libraries by name (with partial matches)
- ✅ Handle empty search results
- ✅ Display creation form with available users

#### CREATE Operations
- ✅ Create library with valid data (all fields)
- ✅ Create library with minimal data
- ✅ Fail validation with missing required fields

#### UPDATE Operations
- ✅ Update all fields of a library
- ✅ Update only specific fields (partial update)
- ✅ Handle non-existent library (404 error)
- ✅ Ignore empty values in update

#### DELETE Operations
- ✅ Delete existing library
- ✅ Handle deletion of non-existent library
- ✅ Delete library with associated pessoas (cascade)

### UserController (User Management)

#### READ Operations
- ✅ List all users
- ✅ Handle empty user list
- ✅ Show specific user details
- ✅ Handle non-existent user access

#### CREATE Operations
- ✅ Create user with valid registration data
- ✅ Hash password on creation
- ✅ Prevent duplicate email registration
- ✅ Fail validation with missing fields

#### UPDATE Operations
- ✅ Update all user fields
- ✅ Update partial user information
- ✅ Handle non-existent user update
- ✅ Prevent duplicate email on update

#### DELETE Operations
- ✅ Delete existing user
- ✅ Handle non-existent user deletion
- ✅ Remove user from database

### PessoaController (Person Management)

#### READ Operations
- ✅ List all pessoas
- ✅ Handle empty list
- ✅ Display creation form

#### CREATE Operations
- ✅ Create pessoa with valid data
- ✅ Validate password matching
- ✅ Hash password on storage
- ✅ Fail with mismatched passwords

#### UPDATE Operations
- ✅ Update all pessoa fields
- ✅ Update partial pessoa data
- ✅ Update password with confirmation
- ✅ Skip password update if empty
- ✅ Validate password matching on update

#### DELETE Operations
- ✅ Destroy method exists (partially implemented)

### BibliotecaPessoaController (Associations)

#### FORM Operations
- ✅ Display add pessoa form
- ✅ Filter out already-associated pessoas
- ✅ Handle empty list when all are associated

#### LINK Operations
- ✅ Link pessoa to biblioteca successfully
- ✅ Prevent linking non-existent pessoa
- ✅ Prevent duplicate associations
- ✅ Link multiple pessoas sequentially
- ✅ Preserve existing associations (syncWithoutDetaching)

## Best Practices Demonstrated

### 1. Test Organization
- Clear test grouping with comments
- Descriptive test method names
- Organized by operation type (READ, CREATE, UPDATE, DELETE)

### 2. Test Independence
- `RefreshDatabase` trait ensures clean state
- In-memory SQLite database for isolation
- No shared state between tests

### 3. Assertion Patterns
- HTTP response status verification
- Redirect destination validation
- Session message/error checking
- Database state verification (assertDatabaseHas/Missing)

### 4. Error Scenario Coverage
- Invalid data handling
- Missing required fields
- Duplicate prevention
- Non-existent resource handling
- Permission/authorization scenarios

### 5. Business Logic Testing
- Password hashing verification
- Relationship preservation
- Search functionality
- Partial updates
- Cascade operations

## GitHub Actions Workflow

To set up automated testing on pull requests, create `.github/workflows/run-tests.yml`:

```yaml
name: Integration Tests

on:
  pull_request:
    branches:
      - develop
      - master
  push:
    branches:
      - develop

jobs:
  test:
    runs-on: ubuntu-latest
    
    strategy:
      matrix:
        php-version: ['8.3']
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php-version }}
          extensions: dom, curl, libxml, mbstring, zip, pcntl, pdo, sqlite, pdo_sqlite, bcmath, soap, intl, gd, exif, iconv
          coverage: xdebug
      
      - name: Cache composer dependencies
        uses: actions/cache@v3
        with:
          path: vendor
          key: composer-${{ hashFiles('**/composer.lock') }}
          restore-keys: |
            composer-
      
      - name: Install Composer dependencies
        run: composer install -q --no-ansi --no-interaction --no-scripts --no-suggest --prefer-dist
      
      - name: Generate application key
        run: php artisan key:generate --env=testing
      
      - name: Run migrations
        run: php artisan migrate --env=testing
        env:
          DB_CONNECTION: sqlite
          DB_DATABASE: :memory:
      
      - name: Run Feature Tests
        run: php artisan test --testsuite=Feature
        env:
          DB_CONNECTION: sqlite
          DB_DATABASE: :memory:
      
      - name: Run Unit Tests
        run: php artisan test --testsuite=Unit
        env:
          DB_CONNECTION: sqlite
          DB_DATABASE: :memory:
```

## Continuous Integration Benefits

1. **Automatic Test Execution**: Tests run on every pull request
2. **Fail Fast**: CI prevents merging broken code
3. **Coverage Reports**: Track test coverage over time
4. **Consistency**: Same environment as local testing
5. **Documentation**: Test results visible in PR checks

## Next Steps

1. Create `.github/workflows/run-tests.yml` with the workflow content above
2. Push the feature branch to GitHub
3. Create a pull request to `develop` branch
4. GitHub Actions will automatically run the test suite
5. Review test results in the PR checks section

## Extending the Tests

To add more tests:

1. Follow the same pattern and naming conventions
2. Group tests by operation type with comments
3. Use descriptive test names that explain the scenario
4. Include docblocks with test details
5. Use RefreshDatabase trait for isolation
6. Verify both success and failure paths

## Test Naming Convention

Tests follow this pattern:
- `test_[operation]_[scenario]`
- Examples:
  - `test_store_creates_biblioteca_with_valid_data`
  - `test_update_fails_with_nonexistent_user`
  - `test_index_filters_by_search_name`

## Resources

- [Laravel Testing Documentation](https://laravel.com/docs/testing)
- [PHPUnit Documentation](https://phpunit.de/)
- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [Testing Best Practices](https://laravel.com/docs/testing#testing-models)
