<?php

declare(strict_types=1);

require_once 'vendor/autoload.php';

use DicomWeb\Parser\DicomWebParser;

// Example of using the parser with a sample DICOMWeb JSON response
$sampleJson = <<<JSON
[
  {
    "00080005": {
      "vr": "CS",
      "Value": [
        "ISO_IR 100"
      ]
    },
    "00080020": {
      "vr": "DA",
      "Value": [
        "20230514"
      ]
    },
    "00080030": {
      "vr": "TM",
      "Value": [
        "143015"
      ]
    },
    "00080050": {
      "vr": "SH",
      "Value": [
        "A12345"
      ]
    },
    "00080060": {
      "vr": "CS",
      "Value": [
        "MR"
      ]
    },
    "00081030": {
      "vr": "LO",
      "Value": [
        "BRAIN STUDY"
      ]
    },
    "00100010": {
      "vr": "PN",
      "Value": [
        {
          "Alphabetic": {
            "FamilyName": "Doe",
            "GivenName": "Jane"
          }
        }
      ]
    },
    "00100020": {
      "vr": "LO",
      "Value": [
        "12345678"
      ]
    },
    "0020000D": {
      "vr": "UI",
      "Value": [
        "1.2.840.10008.5.1.4.1.1.4.1.123456789"
      ]
    },
    "0020000E": {
      "vr": "UI",
      "Value": [
        "1.2.840.10008.5.1.4.1.1.4.1.123456789.1"
      ]
    },
    "00200011": {
      "vr": "IS",
      "Value": [
        1
      ]
    },
    "00200013": {
      "vr": "IS",
      "Value": [
        2
      ]
    },
    "00080018": {
      "vr": "UI",
      "Value": [
        "1.2.840.10008.5.1.4.1.1.4.1.123456789.1.2"
      ]
    },
    "00080016": {
      "vr": "UI",
      "Value": [
        "1.2.840.10008.5.1.4.1.1.4"
      ]
    }
  }
]
JSON;

// Create a parser instance
$parser = new DicomWebParser();

try {
    // Parse the JSON into DICOM instances
    $instances = $parser->parseInstances($sampleJson);

    echo "Successfully parsed " . count($instances) . " DICOM instances\n\n";

    // Display information about the first instance
    $firstInstance = $instances[0];
    echo "Instance Information:\n";
    echo "- SOP Instance UID: " . $firstInstance->getSopInstanceUid() . "\n";
    echo "- Study Instance UID: " . $firstInstance->getStudyInstanceUid() . "\n";
    echo "- Series Instance UID: " . $firstInstance->getSeriesInstanceUid() . "\n";
    echo "- Modality: " . $firstInstance->getModality() . "\n";
    echo "- Patient ID: " . $firstInstance->getFirstValue('00100020') . "\n";

    // Access patient name (which has components)
    $patientName = $firstInstance->getFirstValue('00100010');
    if (is_array($patientName)) {
        echo "- Patient Name: " . $patientName['family'] . ", " . $patientName['given'] . "\n";
    }

    // Get study date
    $studyDateValue = $firstInstance->getFirstValue('00080020');
    if ($studyDateValue instanceof \DateTimeImmutable) {
        echo "- Study Date: " . $studyDateValue->format('Y-m-d') . "\n";
    } else {
        echo "- Study Date (raw): " . $studyDateValue . "\n";
    }

    echo "\n";

    // Parse the JSON into a study structure
    $study = $parser->parseStudy($sampleJson);

    echo "Study Information:\n";
    echo "- Study Instance UID: " . $study->getStudyInstanceUid() . "\n";
    echo "- Series Count: " . $study->getSeriesCount() . "\n";
    echo "- Total Instance Count: " . $study->getTotalInstanceCount() . "\n";
    echo "- Modalities: " . implode(", ", $study->getModalities()) . "\n";

    // Access patient info from the study
    $patientId = $study->getPatientId();
    echo "- Patient ID: " . $patientId . "\n";

    // Get all series
    $seriesList = $study->getSeries();
    foreach ($seriesList as $index => $series) {
        echo "\nSeries #" . ($index + 1) . ":\n";
        echo "- Series Instance UID: " . $series->getSeriesInstanceUid() . "\n";
        echo "- Instance Count: " . $series->getInstanceCount() . "\n";
        echo "- Modality: " . $series->getModality() . "\n";

        // Sort instances by instance number
        $series->sortInstancesByNumber();

        // Get all instances in this series
        $seriesInstances = $series->getInstances();
        foreach ($seriesInstances as $idx => $instance) {
            echo "  Instance #" . ($idx + 1) . ": " . $instance->getSopInstanceUid() . "\n";
        }
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
