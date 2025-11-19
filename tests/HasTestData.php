<?php

namespace Aurabx\DicomWebParser\Tests;

trait HasTestData
{
    private function getTestData(): array
    {
        $studyInstanceUid = '1.2.3.4.5';

        $metadata = [
            [
                '0020000D' => ['vr' => 'UI', 'Value' => [$studyInstanceUid]],
                '00081030' => ['vr' => 'LO', 'Value' => ['Brain CT and MRI Study']],
                '00080020' => ['vr' => 'DA', 'Value' => ['20240101']],
                '00080030' => ['vr' => 'TM', 'Value' => ['120000']],
                '00080050' => ['vr' => 'SH', 'Value' => ['ACC123456']],
                '00080090' => ['vr' => 'PN', 'Value' => [[
                    'Alphabetic' => [
                        'FamilyName' => 'Smith',
                        'GivenName' => 'John',
                        'MiddleName' => '',
                        'NamePrefix' => 'Dr.',
                        'NameSuffix' => '',
                    ]
                ]]],
                '00080080' => ['vr' => 'LO', 'Value' => ['Test Hospital']],
                '00080081' => ['vr' => 'ST', 'Value' => ['123 Medical Drive, Boston MA 02115']],
                '00100020' => ['vr' => 'LO', 'Value' => ['PatientID123']],
                '00100010' => ['vr' => 'PN', 'Value' => [[
                    'Alphabetic' => [
                        'FamilyName' => 'Smith',
                        'GivenName' => 'John',
                    ]
                ]]],
                '00100040' => ['vr' => 'CS', 'Value' => ['M']],
                '00100030' => ['vr' => 'DA', 'Value' => ['19800101']],
                '00101010' => ['vr' => 'AS', 'Value' => ['45']],
                '0020000E' => ['vr' => 'UI', 'Value' => ['series-uid-1']],
                '0008103E' => ['vr' => 'LO', 'Value' => ['Axial CT']],
                '00080060' => ['vr' => 'CS', 'Value' => ['CT']],
                '00200011' => ['vr' => 'IS', 'Value' => ['2']],
                '00180015' => ['vr' => 'CS', 'Value' => ['HEAD']],
            ],
            [
                '0020000D' => ['vr' => 'UI', 'Value' => [$studyInstanceUid]],
                '00081030' => ['vr' => 'LO', 'Value' => ['Brain CT and MRI Study']],
                '00080020' => ['vr' => 'DA', 'Value' => ['20240101']],
                '00080030' => ['vr' => 'TM', 'Value' => ['120000']],
                '00080050' => ['vr' => 'SH', 'Value' => ['ACC123456']],
                '00080090' => ['vr' => 'PN', 'Value' => [[
                    'Alphabetic' => [
                        'FamilyName' => 'Smith',
                        'GivenName' => 'John',
                        'MiddleName' => '',
                        'NamePrefix' => 'Dr.',
                        'NameSuffix' => '',
                    ]
                ]]],
                '00080080' => ['vr' => 'LO', 'Value' => ['Test Hospital']],
                '00080081' => ['vr' => 'ST', 'Value' => ['123 Medical Drive, Boston MA 02115']],
                '00100020' => ['vr' => 'LO', 'Value' => ['PatientID123']],
                '00100010' => ['vr' => 'PN', 'Value' => [[
                    'Alphabetic' => [
                        'FamilyName' => 'Smith',
                        'GivenName' => 'John',
                    ]
                ]]],
                '00100040' => ['vr' => 'CS', 'Value' => ['M']],
                '00100030' => ['vr' => 'DA', 'Value' => ['19800101']],
                '00101010' => ['vr' => 'AS', 'Value' => ['45']],
                '0020000E' => ['vr' => 'UI', 'Value' => ['series-uid-2']],
                '0008103E' => ['vr' => 'LO', 'Value' => ['Sagittal MRI']],
                '00080060' => ['vr' => 'CS', 'Value' => ['MR']],
                '00200011' => ['vr' => 'IS', 'Value' => ['3']],
                '00180015' => ['vr' => 'CS', 'Value' => ['HEAD']],
            ],
            [
                '0020000D' => ['vr' => 'UI', 'Value' => [$studyInstanceUid]],
                '00081030' => ['vr' => 'LO', 'Value' => ['Complete Neurological Examination']],
                '00080020' => ['vr' => 'DA', 'Value' => ['20240101']],
                '00080030' => ['vr' => 'TM', 'Value' => ['115500']],
                '00080050' => ['vr' => 'SH', 'Value' => ['ACC123456']],
                '00080090' => ['vr' => 'PN', 'Value' => [[
                    'Alphabetic' => [
                        'FamilyName' => 'Smith',
                        'GivenName' => 'John',
                        'MiddleName' => '',
                        'NamePrefix' => 'Dr.',
                        'NameSuffix' => '',
                    ]
                ]]],
                '00080080' => ['vr' => 'LO', 'Value' => ['Test Hospital']],
                '00080081' => ['vr' => 'ST', 'Value' => ['123 Medical Drive, Boston MA 02115']],
                '00100020' => ['vr' => 'LO', 'Value' => ['PatientID123']],
                '00100010' => ['vr' => 'PN', 'Value' => [[
                    'Alphabetic' => [
                        'FamilyName' => 'Smith',
                        'GivenName' => 'John',
                        'MiddleName' => '',
                        'NamePrefix' => '',
                        'NameSuffix' => '',
                    ]
                ]]],
                '00100040' => ['vr' => 'CS', 'Value' => ['M']],
                '00100030' => ['vr' => 'DA', 'Value' => ['19800101']],
                '00101010' => ['vr' => 'AS', 'Value' => ['45']],
                '0020000E' => ['vr' => 'UI', 'Value' => ['series-uid-3']],
                '0008103E' => ['vr' => 'LO', 'Value' => ['X-Ray']],
                '00080060' => ['vr' => 'CS', 'Value' => ['CR']],
                '00200011' => ['vr' => 'IS', 'Value' => ['1']],
                '00180015' => ['vr' => 'CS', 'Value' => ['HEAD']],
            ]
        ];

        return array_map(static function (array $series, int $index) {
            $series += [
                '00080022' => ['vr' => 'DA', 'Value' => ['20240101']], // AcquisitionDate
                '00080023' => ['vr' => 'DA', 'Value' => ['20240101']], // ContentDate
                '00081070' => ['vr' => 'PN', 'Value' => [[
                    'Alphabetic' => [
                        'FamilyName' => 'Radiographer',
                        'GivenName' => 'Alice',
                        'MiddleName' => '',
                        'NamePrefix' => '',
                        'NameSuffix' => '',
                    ]
                ]]],
                '00081090' => ['vr' => 'LO', 'Value' => ['Philips^Ingenia']],
                '00080070' => ['vr' => 'LO', 'Value' => ['Philips']],
                '00181030' => ['vr' => 'LO', 'Value' => ['Neuro Head Protocol']],
                '00180024' => ['vr' => 'SH', 'Value' => ['SE_T1W_AXIAL']],
                '00180021' => ['vr' => 'DS', 'Value' => ['5.2']],
                '00080021' => ['vr' => 'DA', 'Value' => ['20240101']],
                '00080031' => ['vr' => 'TM', 'Value' => ['121500']],
                '00181060' => ['vr' => 'DS', 'Value' => ['120']],
                '00180081' => ['vr' => 'IS', 'Value' => ['12']],
                '00081111' => [
                    'vr' => 'SQ',
                    'Value' => [
                        [
                            '00080050' => ['vr' => 'SH', 'Value' => ['ACC123456']]
                        ],
                    ],
                ],
                '00200020' => ['vr' => 'CS', 'Value' => ['HFS']],
                '00185100' => ['vr' => 'CS', 'Value' => ['PA']],
                '00101000' => ['vr' => 'LO', 'Value' => ['HOSP987654']],
                '00101002' => [
                    'vr' => 'SQ',
                    'Value' => [
                        [
                            '00100020' => ['vr' => 'LO', 'Value' => ['HOSP12345']],
                            '00100024' => ['vr' => 'LO', 'Value' => ['HospitalSystemX']],
                            '00100021' => [
                                'vr' => 'SQ',
                                'Value' => [
                                    [
                                        '00400032' => ['vr' => 'UI', 'Value' => ['1.2.3.4.5.6.7.8']],
                                        '00400033' => ['vr' => 'CS', 'Value' => ['ISO']],
                                    ]
                                ]
                            ]
                        ],
                        [
                            '00100020' => ['vr' => 'LO', 'Value' => ['RIS123']],
                            '00100024' => ['vr' => 'LO', 'Value' => ['RIS']],
                            '00100021' => [
                                'vr' => 'SQ',
                                'Value' => [
                                    [
                                        '00400032' => ['vr' => 'UI', 'Value' => ['1.2.1.1.1']],
                                        '00400033' => ['vr' => 'CS', 'Value' => ['ISO']],
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],

            ];

            return $series;
        }, $metadata, array_keys($metadata));
    }
}
