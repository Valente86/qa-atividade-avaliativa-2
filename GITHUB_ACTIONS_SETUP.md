# GitHub Actions Workflow Setup Guide

## Overview

This guide provides step-by-step instructions to configure and enable the GitHub Actions workflow for automatically running integration tests on every pull request.

## Workflow Configuration

### Step 1: Create Workflow Directory Structure

The GitHub Actions workflow files must be placed in `.github/workflows/` directory at the root of your repository.

### Step 2: Create the Workflow File

Create a new file: `.github/workflows/run-tests.yml`

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
    name: Run Tests
    runs-on: ubuntu-latest
    
    strategy:
      matrix:
        php-version: ['8.3']
    
    steps:
      - name: Checkout code
        uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php-version }}
          extensions: dom, curl, libxml, mbstring, zip, pcntl, pdo, sqlite, pdo_sqlite, bcmath, soap, intl, gd, exif, iconv
          coverage: xdebug
      
      - name: Cache Composer dependencies
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
      
      - name: Run migrations (Feature Tests)
        run: php artisan migrate --env=testing
        env:
          DB_CONNECTION: sqlite
          DB_DATABASE: :memory:
      
      - name: Execute Feature Tests
        run: php artisan test --testsuite=Feature --env=testing
        env:
          DB_CONNECTION: sqlite
          DB_DATABASE: :memory:
      
      - name: Execute Unit Tests
        run: php artisan test --testsuite=Unit --env=testing
        env:
          DB_CONNECTION: sqlite
          DB_DATABASE: :memory:
      
      - name: Generate coverage report
        run: php artisan test --coverage --min=50 --env=testing
        continue-on-error: true
        env:
          DB_CONNECTION: sqlite
          DB_DATABASE: :memory:
      
      - name: Upload coverage to Codecov
        uses: codecov/codecov-action@v3
        if: always()
        with:
          files: ./coverage.xml
          flags: unittests
          name: codecov-umbrella
          fail_ci_if_error: false
```

### Step 3: Commit and Push

```bash
# From your feature branch
git add .github/workflows/run-tests.yml
git commit -m "Configure GitHub Actions workflow for integration tests"
git push origin feature/integration-tests-coverage
```

## Workflow Triggers

The workflow is triggered by:

1. **Pull Requests to `develop` or `master`** branches
   - Runs automatically when PR is created or updated
   - Results appear in PR checks

2. **Direct Push to `develop`** branch
   - Runs on every push to develop

## Workflow Steps Explained

### 1. Checkout Code
Clones the repository branch into the runner environment

### 2. Setup PHP
Installs PHP 8.3 with required extensions using the official setup-php action

### 3. Cache Dependencies
Caches Composer dependencies to speed up subsequent runs

### 4. Install Dependencies
Installs all Composer packages listed in composer.json

### 5. Generate App Key
Creates the Laravel application encryption key needed for testing

### 6. Run Migrations
Executes database migrations in testing environment

### 7. Execute Tests
Runs Feature and Unit tests separately:
- Feature tests: integration tests for endpoints
- Unit tests: business logic tests

### 8. Coverage Report
Generates code coverage report (min 50% coverage)

### 9. Upload Coverage
Uploads coverage data to Codecov for tracking

## Viewing Test Results

### In Pull Requests

1. Open your pull request on GitHub
2. Scroll down to "Checks" section
3. Click "Integration Tests" to expand details
4. View real-time test execution output

### In Actions Tab

1. Go to repository → "Actions" tab
2. Select "Integration Tests" workflow
3. View all workflow runs and their status
4. Click on specific run to see detailed logs

## Environment Variables in Tests

The workflow sets these variables for testing:

```
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
APP_ENV=testing
APP_KEY=<generated-key>
```

These match the phpunit.xml configuration to ensure consistent test environment.

## Troubleshooting

### Issue: Workflow file not found

**Solution:** Ensure the file path is exactly `.github/workflows/run-tests.yml`

### Issue: Tests fail due to missing dependencies

**Solution:** Run locally first to verify all dependencies are in composer.json:
```bash
composer install
php artisan test
```

### Issue: Database errors in CI

**Solution:** Verify migrations run correctly. The workflow runs:
```bash
php artisan migrate --env=testing
```

Check that all migration files are committed to repository.

### Issue: PHP version mismatch

**Solution:** Update the matrix.php-version in workflow:
```yaml
strategy:
  matrix:
    php-version: ['8.3', '8.2']  # Add other versions to test
```

### Issue: Tests timeout

**Solution:** Add timeout to job:
```yaml
jobs:
  test:
    timeout-minutes: 30  # Increase if needed
    runs-on: ubuntu-latest
```

## Best Practices

### 1. Test Quality
- Ensure all tests pass locally before pushing
- Maintain good test coverage (aim for >70%)
- Write descriptive test names

### 2. CI Configuration
- Use consistent environments (local ≈ CI)
- Cache dependencies for speed
- Fail fast on critical errors

### 3. Monitoring
- Check workflow status regularly
- Review failed test logs
- Update workflow as requirements change

### 4. Performance
- Tests should complete in <5 minutes
- Use in-memory SQLite for speed
- Parallelize tests if needed

## Advanced Configuration

### Adding Parallel Testing

```yaml
strategy:
  matrix:
    php-version: ['8.3']
    test-suite: ['Feature', 'Unit']

- name: Execute ${{ matrix.test-suite }} Tests
  run: php artisan test --testsuite=${{ matrix.test-suite }}
```

### Adding Code Quality Checks

```yaml
- name: Run PHPLint
  run: ./vendor/bin/phplint

- name: Run PHPStan
  run: ./vendor/bin/phpstan analyse app tests
```

### Adding Database Services

```yaml
services:
  mysql:
    image: mysql:8.0
    env:
      MYSQL_DATABASE: testing
      MYSQL_ROOT_PASSWORD: root
    options: >-
      --health-cmd="mysqladmin ping"
      --health-interval=10s
      --health-timeout=5s
      --health-retries=3
```

## Integration with Branch Protection

To enforce passing tests before merge:

1. Go to Settings → Branches
2. Click "Add rule"
3. Branch name pattern: `develop`
4. Enable "Require status checks to pass before merging"
5. Select "Integration Tests" check
6. Enable "Require branches to be up to date before merging"

Now all PRs must pass tests before merging!

## Next Steps

After setting up the workflow:

1. Create a pull request with these changes
2. Watch the workflow execute automatically
3. Verify all tests pass
4. Review the workflow logs
5. Merge to develop branch
6. Enable branch protection rules

## Resources

- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [Setup PHP Action](https://github.com/shivammathur/setup-php)
- [Laravel Testing Guide](https://laravel.com/docs/testing)
- [GitHub Status Checks](https://docs.github.com/en/repositories/configuring-branches-and-merges-in-your-repository/managing-protected-branches/about-protected-branches#require-status-checks-before-merging)
