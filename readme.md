# DICOMWeb Parser for PHP

A PHP library for parsing DICOMWeb JSON responses into structured PHP objects.

## Requirements

- PHP 8.2 or higher
- ext-json

## Installation

```bash
composer require your-vendor/dicomweb-parser
```

## Usage

### Basic Usage

```php
<?php

use Aurabx\DicomWebParser\Parser;

// Your DICOMWeb JSON response
dollarjsonData = file_get_contents('dicom_response.json');

// Create a parser instance
$parser = new Parser();

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

// Access a DICOM tag by its standard name
$modality = $firstInstance->getFirstValueByName('Modality');
echo "Modality: " . $modality . "\n";

// Parse the response into a study structure
$study = $parser->parseStudy($jsonData);
echo "Study has " . $study->getSeriesCount() . " series and " .
     $study->getTotalInstanceCount() . " total instances\n";
```

### Working with Series and Studies

```php
<?php

use Aurabx\DicomWebParser\Parser;

$jsonData = file_get_contents('dicom_response.json');
$parser = new Parser();
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

use Aurabx\DicomWebParser\DicomModel\DicomTag;

// Get the descriptive name for a tag
$tagName = DicomTag::getName('00100010');  // Returns "PatientName"

// Format a tag with a separator
$formattedTag = DicomTag::formatTag('00100010', 'comma');  // Returns "0010,0010"
$formattedTag = DicomTag::formatTag('00100010', 'both');   // Returns "(0010,0010)"

// Get the meaning of a Value Representation code
$vrMeaning = DicomTag::getVRMeaning('PN');  // Returns "Person Name"
```

### Using the Tag Dictionary

```php
<?php

use Aurabx\DicomWebParser\DicomDictionary;

// Lookup tag ID by name
$tagId = DicomDictionary::getTagIdByName('ImagingFrequency');  // Returns '00180084'

// Get full metadata
$info = DicomDictionary::getTagInfo('00180084');

// Get tag VR or description
$vr = DicomDictionary::getTagVR('00180084');
$desc = DicomDictionary::getTagDescription('00180084');
```

### Testing with Custom Tags

```php
<?php

use Aurabx\DicomWebParser\DicomTagLoader;
use Aurabx\DicomWebParser\DicomDictionary;

$loader = new DicomTagLoader();
$loader->loadFromArray([
    '00100020' => ['name' => 'PatientID', 'vr' => 'LO'],
    '00180084' => ['name' => 'ImagingFrequency', 'vr' => 'DS'],
]);

DicomDictionary::preload($loader);
```

## Key Features

- Parse DICOMWeb JSON responses into structured PHP objects
- Full support for DICOM instances, series, and studies
- Access DICOM elements by tag ID or friendly name
- Preload tag dictionaries for testing or performance
- Includes tag metadata lookup, name resolution, VR decoding
- Fully type hinted (PHP 8.2+ recommended)

## Class Structure

- `Parser` - Main parser for DICOMWeb JSON
- `DicomElement` - Represents a DICOM attribute
- `DicomInstance` - Single DICOM SOP instance
- `DicomSeries` - Group of instances
- `DicomStudy` - Group of series
- `DicomTag` - Static utilities for tags
- `DicomTagLoader` - Loads tag metadata from JSON
- `DicomDictionary` - Global tag lookup facade
- `ParserException` - Exception class

## License

MIT
