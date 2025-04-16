# DICOMWeb Parser for PHP

A PHP library for parsing DICOMWeb JSON responses into structured PHP objects.

## Requirements

- PHP 8.0 or higher
- ext-json

## Installation

```bash
composer require your-vendor/dicomweb-parser
```

## Usage

### Basic Usage

```php
<?php

use DicomWeb\Parser\DicomWebParser;

// Your DICOMWeb JSON response
$jsonData = file_get_contents('dicom_response.json');

// Create a parser instance
$parser = new DicomWebParser();

// Parse the JSON into DICOM instances
$instances = $parser->parseInstances($jsonData);

// Display information about the first instance
$firstInstance = $instances[0];
echo "SOP Instance UID: " . $firstInstance->getSopInstanceUid() . "\n";
echo "Study Instance UID: " . $firstInstance->getStudyInstanceUid() . "\n";
echo "Series Instance UID: " . $firstInstance->getSeriesInstanceUid() . "\n";
echo "Modality: " . $firstInstance->getModality() . "\n";

// Access any DICOM tag by its tag ID
$patientId = $firstInstance->getFirstValue('00100020');
echo "Patient ID: " . $patientId . "\n";

// Parse the response into a study structure
$study = $parser->parseStudy($jsonData);
echo "Study has " . $study->getSeriesCount() . " series and " . 
     $study->getTotalInstanceCount() . " total instances\n";
```

### Working with Series and Studies

```php
<?php

use DicomWeb\Parser\DicomWebParser;

$jsonData = file_get_contents('dicom_response.json');
$parser = new DicomWebParser();
$study = $parser->parseStudy($jsonData);

// Get all series in the study
$seriesList = $study->getSeries();

foreach ($seriesList as $series) {
    echo "Series UID: " . $series->getSeriesInstanceUid() . "\n";
    echo "Series has " . $series->getInstanceCount() . " instances\n";
    
    // Sort instances by instance number
    $series->sortInstancesByNumber();
    
    // Get all instances in this series
    $instances = $series->getInstances();
    
    foreach ($instances as $instance) {
        echo "  Instance UID: " . $instance->getSopInstanceUid() . "\n";
    }
}
```

### Using Tag Utilities

```php
<?php

use DicomWeb\Parser\DicomTag;

// Get the descriptive name for a tag
$tagName = DicomTag::getName('00100010');  // Returns "PatientName"

// Format a tag with a separator
$formattedTag = DicomTag::formatTag('00100010', 'comma');  // Returns "0010,0010"
$formattedTag = DicomTag::formatTag('00100010', 'both');   // Returns "(0010,0010)"

// Get the meaning of a Value Representation code
$vrMeaning = DicomTag::getVRMeaning('PN');  // Returns "Person Name"
```

## Key Features

- Parse DICOMWeb JSON responses into structured PHP objects
- Support for DICOM instances, series, and studies
- Automatic handling of common DICOM VR types
- Utility class for working with DICOM tags
- Full type hinting for PHP 8.0+

## Class Structure

- `DicomParser` - Main parser class
- `DicomElement` - Represents a DICOM element (attribute/value pair)
- `DicomInstance` - Represents a DICOM instance (single image or object)
- `DicomSeries` - Represents a DICOM series (collection of instances)
- `DicomStudy` - Represents a DICOM study (collection of series)
- `DicomTag` - Utility class for working with DICOM tags
- `ParserException` - Exception thrown by the parser

## License

MIT
