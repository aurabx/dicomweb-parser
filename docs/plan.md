# DICOMWeb Parser Library - Implementation Plan

This plan outlines step-by-step instructions for implementing the DICOMWeb Parser library. Follow these steps to create a complete, well-structured library.

## Phase 1: Setup Project Structure

1. **Create Basic Directory Structure**
   ```
   mkdir -p dicomweb-parser/src dicomweb-parser/tests/fixtures
   cd dicomweb-parser
   ```

2. **Initialize Composer**
   ```
   composer init
   ```
    - Set package name to your vendor/dicomweb-parser
    - Add description "A PHP library for parsing DICOMWeb JSON responses"
    - Set license to "MIT"
    - Set PHP requirement to ">=8.0"
    - Add ext-json requirement

3. **Set Up Autoloading in composer.json**
   ```json
   "autoload": {
       "psr-4": {
           "DicomWeb\\Parser\\": "src/"
       }
   },
   "autoload-dev": {
       "psr-4": {
           "DicomWeb\\Parser\\Tests\\": "tests/"
       }
   }
   ```

4. **Add Development Dependencies**
   ```
   composer require --dev phpunit/phpunit squizlabs/php_codesniffer
   ```

5. **Create PHPUnit Configuration**
   ```
   cp vendor/phpunit/phpunit/phpunit.xml.dist ./phpunit.xml.dist
   ```
    - Update test suite path to point to your tests directory

## Phase 2: Core Classes Implementation

### 1. Exception Class

Create `src/ParserException.php`:
```php
<?php

declare(strict_types=1);

namespace DicomWeb\Parser;

class ParserException extends \Exception 
{
}
```

### 2. DicomElement Class

Create `src/DicomElement.php` with:
- Constructor taking VR code and value
- Methods to get VR, value, first value
- Method to check if element has a value
- String conversion method

### 3. DicomInstance Class

Create `src/DicomInstance.php` with:
- Methods to add, get, and check elements
- Helper methods for common tags (StudyInstanceUID, SeriesInstanceUID, etc.)
- Magic method to handle dynamic getters
- Method to convert to array

### 4. DicomSeries Class

Create `src/DicomSeries.php` with:
- Constructor taking instances and optional series UID
- Methods to get and add instances
- Methods for series metadata (modality, series number, etc.)
- Method to sort instances by instance number

### 5. DicomStudy Class

Create `src/DicomStudy.php` with:
- Constructor taking series and study UID
- Methods to get and add series
- Methods for study-level information (patient name, study date, etc.)
- Method to get all instances across all series

### 6. DicomTag Utility Class

Create `src/DicomTag.php` with:
- Static constants for common DICOM tags
- Methods to get tag names and IDs
- Methods to format and normalize tags
- Methods for VR information

### 7. DicomParser Class

Create `src/DicomParser.php` with:
- Methods to parse instances and studies
- Methods to handle different VR types
- Helper methods for parsing complex elements

## Phase 3: Testing

### 1. Create Test Fixtures

Create sample JSON files in `tests/fixtures/`:
- `instance.json`: Single instance
- `series.json`: Multiple instances in one series
- `study.json`: Multiple series in one study

### 2. Write Unit Tests

Create test classes:
- `tests/DicomParserTest.php`: Test parsing functionality
- `tests/DicomTagTest.php`: Test tag utility functions

### 3. Run Tests

```
vendor/bin/phpunit
```

## Phase 4: Documentation

### 1. Create README.md

Include:
- Introduction and purpose
- Installation instructions
- Basic usage examples
- API documentation overview
- License information

### 2. Create Detailed API Documentation

Document each class and method with PHPDoc comments:
- Parameter types and descriptions
- Return types and descriptions
- Exception information
- Usage examples

### 3. Add LICENSE File

Add MIT license text to `LICENSE` file.

## Phase 5: Quality Assurance

### 1. Check Code Style

```
vendor/bin/phpcs --standard=PSR12 src tests
```

### 2. Fix Code Style Issues

```
vendor/bin/phpcbf --standard=PSR12 src tests
```

### 3. Ensure Test Coverage

Check that all code paths are tested.

## Phase 6: Packaging and Distribution

### 1. Finalize composer.json

Ensure all requirements and metadata are correct.

### 2. Create a Git Repository

```
git init
git add .
git commit -m "Initial implementation of DICOMWeb Parser library"
```

### 3. Push to GitHub or Other Repository

Create a repository and push your code.

### 4. Release Version

Tag a release version:
```
git tag v1.0.0
git push --tags
```

## Implementation Tips

1. **Start Small**: Begin with the core functionality and add features incrementally.
2. **Test-Driven Development**: Write tests before or alongside your implementation.
3. **Handle Edge Cases**: Consider all possible input variations and error conditions.
4. **Keep Performance in Mind**: Optimize for memory efficiency with large datasets.
5. **Maintain Type Safety**: Use PHP 8.3 type hints consistently.
6. **Follow PSR-12**: Adhere to PHP coding standards for consistency.
7. **Document as You Go**: Add PHPDoc comments while coding, not after.

## Timeline

- **Day 1**: Project setup and basic class structure
- **Day 2-3**: Core class implementation
- **Day 4**: Parser implementation
- **Day 5**: Testing and bug fixes
- **Day 6**: Documentation and quality assurance
- **Day 7**: Finalization and distribution

By following this plan, you'll create a robust, well-structured PHP library for parsing DICOMWeb responses that follows best practices for modern PHP development.
