# DICOM Value Representation (VR) Guide

This guide explains how to handle different DICOM Value Representations (VRs) in the parser.

## Common Value Representations

| VR | Name | Description | PHP Type | Parsing Strategy |
|----|------|------------|----------|-----------------|
| AE | Application Entity | Application name (16 bytes max) | string | No special handling |
| AS | Age String | Age with format nnnD/W/M/Y | string | Could convert to a standardized format |
| CS | Code String | Fixed values (16 bytes max) | string | No special handling |
| DA | Date | Format YYYYMMDD | DateTimeImmutable | Parse to DateTimeImmutable |
| DS | Decimal String | Floating point numbers | float | Convert to float |
| DT | Date Time | Format YYYYMMDDHHMMSS.FFFFFF | DateTimeImmutable | Parse to DateTimeImmutable |
| FL | Floating Point Single | 32-bit IEEE float | float | Already numeric in JSON |
| FD | Floating Point Double | 64-bit IEEE float | float | Already numeric in JSON |
| IS | Integer String | Integer number | int | Convert to int |
| LO | Long String | Text (64 chars max) | string | No special handling |
| LT | Long Text | Text (10240 chars max) | string | No special handling |
| PN | Person Name | Person name components | array | Parse components (family, given, etc.) |
| SH | Short String | Text (16 chars max) | string | No special handling |
| SL | Signed Long | 32-bit signed integer | int | Already numeric in JSON |
| SQ | Sequence | Sequence of items | array | Parse nested sequence items |
| SS | Signed Short | 16-bit signed integer | int | Already numeric in JSON |
| ST | Short Text | Text (1024 chars max) | string | No special handling |
| TM | Time | Format HHMMSS.FFFFFF | string | Format as HH:MM:SS |
| UI | Unique Identifier | UID (64 chars max) | string | No special handling |
| UL | Unsigned Long | 32-bit unsigned integer | int | Already numeric in JSON |
| US | Unsigned Short | 16-bit unsigned integer | int | Already numeric in JSON |
| UT | Unlimited Text | Text (unlimited length) | string | No special handling |

## Implementation Details

### Person Name (PN)

Person names in DICOM can be complex with multiple components:

```json
"00100010": {
  "vr": "PN",
  "Value": [
    {
      "Alphabetic": {
        "FamilyName": "Doe",
        "GivenName": "John",
        "MiddleName": "Robert",
        "NamePrefix": "Dr",
        "NameSuffix": "Jr"
      }
    }
  ]
}
```

Parse this into a structured array:

```php
[
    'family' => 'Doe',
    'given' => 'John',
    'middle' => 'Robert',
    'prefix' => 'Dr',
    'suffix' => 'Jr'
]
```

### Date (DA) and Time (TM)

DICOM dates are in format YYYYMMDD:

```json
"00080020": {
  "vr": "DA",
  "Value": [
    "20230514"
  ]
}
```

Parse into a DateTimeImmutable:

```php
$year = substr($dateStr, 0, 4);
$month = substr($dateStr, 4, 2);
$day = substr($dateStr, 6, 2);
$date = new \DateTimeImmutable("$year-$month-$day");
```

DICOM times are in format HHMMSS.FFFFFF:

```json
"00080030": {
  "vr": "TM",
  "Value": [
    "142211.123456"
  ]
}
```

Format into a readable time string:

```php
$hours = substr($timeStr, 0, 2);
$minutes = substr($timeStr, 2, 2);
$seconds = substr($timeStr, 4, 2);
$fractions = substr($timeStr, 6);
$time = "$hours:$minutes:$seconds" . ($fractions ? ".$fractions" : "");
```

### Sequence (SQ)

Sequences contain nested datasets:

```json
"00400275": {
  "vr": "SQ",
  "Value": [
    {
      "00400009": {
        "vr": "SH",
        "Value": [
          "SCHEDULED"
        ]
      }
    },
    {
      "00400009": {
        "vr": "SH",
        "Value": [
          "ARRIVED"
        ]
      }
    }
  ]
}
```

Parse each item in the sequence recursively:

```php
$items = [];
foreach ($element['Value'] as $item) {
    $itemElements = [];
    foreach ($item as $itemTag => $itemElement) {
        $itemElements[$itemTag] = $this->parseElement($itemElement);
    }
    $items[] = $itemElements;
}
$value = $items;
```

## Error Handling

When parsing VRs, consider these error cases:

1. **Missing Values**: Some elements may not have a "Value" key
2. **Invalid Formats**: Dates, times, and other structured formats may not be valid
3. **Unexpected Types**: Values may not match the expected type for the VR

Always implement fallback logic to handle these cases. For example, if date parsing fails, return the original string.

## Extensibility

The parser should be extensible to handle additional or custom VRs:

1. Use a strategy pattern to separate parsing logic for different VRs
2. Allow registration of custom VR handlers
3. Keep VR parsing logic separate from the main parser

This makes the library more maintainable and adaptable to different DICOM implementations.
