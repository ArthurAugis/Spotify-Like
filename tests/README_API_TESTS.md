# 🧪 API Routes Unit Tests - Spotify-Like

## Description
This folder contains unit tests for all API routes in the Spotify-Like application.

## Test Structure

### 1. RecommendationControllerApiTest.php
Tests for recommendation endpoints:
- `GET /recommendations/api` - Retrieve recommendations
- `POST /recommendations/generate` - Generate new recommendations
- `POST /recommendations/{id}/like` - Like a recommendation
- `POST /recommendations/{id}/dismiss` - Dismiss a recommendation
- `GET /recommendations/count` - Recommendations counter

### 2. SearchControllerApiTest.php
Tests for search endpoints:
- `GET /search/ajax` - Real-time AJAX search

### 3. UploadControllerApiTest.php
Tests for upload endpoints:
- `POST /upload/profile-picture` - User avatar upload

## Test Coverage

### Tested scenarios:
✅ **Authentication** - Tests with and without logged-in users  
✅ **Parameter validation** - Valid/invalid parameters  
✅ **Response formats** - Correct JSON structure  
✅ **HTTP status codes** - 200, 400, 401, 500  
✅ **Error handling** - Appropriate error messages  
✅ **Security** - Upload validation, authorization  
✅ **Edge cases** - Non-existent IDs, corrupted files  

### Test types:
- **Functional tests**: Complete HTTP request simulation
- **Integration tests**: Database interaction
- **Validation tests**: Business constraints and rules
- **Security tests**: Authentication and authorization

## Running Tests

### Complete tests
```bash
# Run all API tests
php bin/phpunit -c phpunit.api.xml

# With code coverage
php bin/phpunit -c phpunit.api.xml --coverage-html coverage/

# Specific tests
php bin/phpunit tests/Controller/RecommendationControllerApiTest.php
php bin/phpunit tests/Controller/SearchControllerApiTest.php
php bin/phpunit tests/Controller/UploadControllerApiTest.php
```

### Group-specific tests
```bash
# Authentication tests only
php bin/phpunit tests/Controller/ --filter="Unauthenticated"

# Validation tests only
php bin/phpunit tests/Controller/ --filter="Valid"
```

## Configuration

### Test environment variables
- `APP_ENV=test` - Test environment
- Separate database for testing
- Cache disabled for tests

### Test fixtures
Each test class creates its own test data:
- Temporary users
- Test tracks
- Mock recommendations
- Temporary image files

## Main Assertions

### JSON responses
```php
$this->assertResponseIsSuccessful();
$this->assertResponseHeaderSame('content-type', 'application/json');
$responseData = json_decode($this->client->getResponse()->getContent(), true);
$this->assertArrayHasKey('success', $responseData);
```

### Authentication
```php
$this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
$this->assertEquals('Unauthorized', $responseData['error']);
```

### Data structure
```php
$this->assertIsArray($responseData['recommendations']);
$this->assertIsInt($responseData['count']);
$this->assertStringContainsString('/uploads/', $responseData['avatar_url']);
```

## Implemented Best Practices

### 1. Test isolation
- Each test is independent
- Automatic data cleanup
- No side effects between tests

### 2. Realistic test data
- Users with all required properties
- Dynamically created temporary files
- Respected entity relationships

### 3. Complete coverage
- Positive and negative tests
- Error and nominal cases
- All parameters and headers

### 4. Precise assertions
- JSON structure verification
- Status code validation
- Data type checking

## Quality Metrics

### Expected coverage:
- **API Controllers**: 95%+
- **Used Services**: 85%+
- **Error cases**: 100%

### Success criteria:
- All tests pass
- No regressions detected
- Acceptable performance (<1s per test)
- Sufficient code coverage

## Maintenance

### Adding new tests:
1. Create a new `testNewScenario()` method
2. Follow naming conventions
3. Add appropriate assertions
4. Document the tested case

### Updating tests:
- Adapt to API changes
- Maintain code coverage
- Verify new security constraints

---

**Note**: These tests ensure the reliability and security of your Spotify-Like application APIs. They constitute an essential element of your quality strategy.