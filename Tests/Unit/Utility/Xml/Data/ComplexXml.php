<?php

declare(strict_types=1);

return [
    'MediaInfo' => [
        '@namespaces'     => [
            ''    => 'https://mediaarea.net/mediainfo',
            'xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
        ],
        '@attributes'     => [
            'version'            => '2.0',
            'xsi:schemaLocation' => 'https://mediaarea.net/mediainfo https://mediaarea.net/mediainfo/mediainfo_2_0.xsd',
        ],
        'creatingLibrary' => [
            '@attributes' => [
                'version' => '21.09',
                'url'     => 'https://mediaarea.net/MediaInfo',
            ],
            '@nodeValue'  => 'MediaInfoLib',
            '@position'   => 0,
        ],
        'media'           => [
            '@attributes' => [
                'ref' => 'UnitTest.mp4',
            ],
            'track'       => [
                [
                    '@attributes'              => [
                        'type' => 'General',
                    ],
                    'AudioCount'               => [
                        '@nodeValue' => 1,
                        '@position'  => 0,
                    ],
                    'CodecID'                  => [
                        '@nodeValue' => 'mp42',
                        '@position'  => 1,
                    ],
                    'CodecID_Compatible'       => [
                        '@nodeValue' => 'isom/mp42',
                        '@position'  => 2,
                    ],
                    'DataSize'                 => [
                        '@nodeValue' => 9920847,
                        '@position'  => 3,
                    ],
                    'Duration'                 => [
                        '@nodeValue' => 30.144,
                        '@position'  => 4,
                    ],
                    'Encoded_Date'             => [
                        '@nodeValue' => 'UTC 2015-11-18 10:51:14',
                        '@position'  => 5,
                    ],
                    'File_Modified_Date'       => [
                        '@nodeValue' => 'UTC 2015-11-18 11:07:24',
                        '@position'  => 6,
                    ],
                    'File_Modified_Date_Local' => [
                        '@nodeValue' => '2015-11-18 12:07:24',
                        '@position'  => 7,
                    ],
                    'FileExtension'            => [
                        '@nodeValue' => 'mp4',
                        '@position'  => 8,
                    ],
                    'FileSize'                 => [
                        '@nodeValue' => 9936209,
                        '@position'  => 9,
                    ],
                    'FooterSize'               => [
                        '@nodeValue' => 15338,
                        '@position'  => 10,
                    ],
                    'Format'                   => [
                        '@nodeValue' => 'MPEG-4',
                        '@position'  => 11,
                    ],
                    'Format_Profile'           => [
                        '@nodeValue' => 'Base Media',
                        '@position'  => 12,
                    ],
                    'FrameCount'               => [
                        '@nodeValue' => 753,
                        '@position'  => 13,
                    ],
                    'HeaderSize'               => [
                        '@nodeValue' => 24,
                        '@position'  => 14,
                    ],
                    'IsStreamable'             => [
                        '@nodeValue' => 'No',
                        '@position'  => 15,
                    ],
                    'OverallBitRate_Mode'      => [
                        '@nodeValue' => 'VBR',
                        '@position'  => 16,
                    ],
                    'StreamSize'               => [
                        '@nodeValue' => 15378,
                        '@position'  => 17,
                    ],
                    'Tagged_Date'              => [
                        '@nodeValue' => 'UTC 2015-11-18 10:51:14',
                        '@position'  => 18,
                    ],
                    'VideoCount'               => [
                        '@nodeValue' => 1,
                        '@position'  => 19,
                    ],
                    'xsi:OverallBitRate'       => [
                        '@nodeValue' => 2636998,
                        '@position'  => 20,
                    ],
                    'xsi:FrameRate'            => [
                        '@nodeValue' => 25,
                        '@position'  => 21,
                    ],
                    '@position'                => 0,
                ],
                [
                    '@attributes'                       => [
                        'type' => 'Video',
                    ],
                    'BitDepth'                          => [
                        '@nodeValue' => 8,
                        '@position'  => 0,
                    ],
                    'BitRate'                           => [
                        '@nodeValue' => 2474941,
                        '@position'  => 1,
                    ],
                    'ChromaSubsampling'                 => [
                        '@nodeValue' => '4:2:0',
                        '@position'  => 2,
                    ],
                    'CodecID'                           => [
                        '@nodeValue' => 'avc1',
                        '@position'  => 3,
                    ],
                    'ColorSpace'                        => [
                        '@nodeValue' => 'YUV',
                        '@position'  => 4,
                    ],
                    'colour_description_present'        => [
                        '@nodeValue' => 'Yes',
                        '@position'  => 5,
                    ],
                    'colour_description_present_Source' => [
                        '@nodeValue' => 'Stream',
                        '@position'  => 6,
                    ],
                    'colour_primaries'                  => [
                        '@nodeValue' => 'BT.709',
                        '@position'  => 7,
                    ],
                    'colour_primaries_Source'           => [
                        '@nodeValue' => 'Stream',
                        '@position'  => 8,
                    ],
                    'colour_range'                      => [
                        '@nodeValue' => 'Limited',
                        '@position'  => 9,
                    ],
                    'colour_range_Source'               => [
                        '@nodeValue' => 'Stream',
                        '@position'  => 10,
                    ],
                    'DisplayAspectRatio'                => [
                        '@nodeValue' => 1.778,
                        '@position'  => 11,
                    ],
                    'Duration'                          => [
                        '@nodeValue' => 30.12,
                        '@position'  => 12,
                    ],
                    'Encoded_Date'                      => [
                        '@nodeValue' => 'UTC 2015-11-18 10:51:14',
                        '@position'  => 13,
                    ],
                    'extra'                             => [
                        'CodecConfigurationBox' => [
                            '@nodeValue' => 'avcC',
                            '@position'  => 0,
                        ],
                        '@position'             => 14,
                    ],
                    'Format'                            => [
                        '@nodeValue' => 'AVC',
                        '@position'  => 15,
                    ],
                    'Format_Level'                      => [
                        '@nodeValue' => 3.2,
                        '@position'  => 16,
                    ],
                    'Format_Profile'                    => [
                        '@nodeValue' => 'High',
                        '@position'  => 17,
                    ],
                    'Format_Settings_CABAC'             => [
                        '@nodeValue' => 'Yes',
                        '@position'  => 18,
                    ],
                    'Format_Settings_RefFrames'         => [
                        '@nodeValue' => 4,
                        '@position'  => 19,
                    ],
                    'FrameCount'                        => [
                        '@nodeValue' => 753,
                        '@position'  => 20,
                    ],
                    'FrameRate'                         => [
                        '@nodeValue' => 25,
                        '@position'  => 21,
                    ],
                    'FrameRate_Mode'                    => [
                        '@nodeValue' => 'CFR',
                        '@position'  => 22,
                    ],
                    'Height'                            => [
                        '@nodeValue' => 720,
                        '@position'  => 23,
                    ],
                    'ID'                                => [
                        '@nodeValue' => 1,
                        '@position'  => 24,
                    ],
                    'Language'                          => [
                        '@nodeValue' => 'en',
                        '@position'  => 25,
                    ],
                    'matrix_coefficients'               => [
                        '@nodeValue' => 'BT.709',
                        '@position'  => 26,
                    ],
                    'matrix_coefficients_Source'        => [
                        '@nodeValue' => 'Stream',
                        '@position'  => 27,
                    ],
                    'PixelAspectRatio'                  => [
                        '@nodeValue' => 1,
                        '@position'  => 28,
                    ],
                    'Rotation'                          => [
                        '@nodeValue' => 0,
                        '@position'  => 29,
                    ],
                    'Sampled_Height'                    => [
                        '@nodeValue' => 720,
                        '@position'  => 30,
                    ],
                    'Sampled_Width'                     => [
                        '@nodeValue' => 1280,
                        '@position'  => 31,
                    ],
                    'ScanType'                          => [
                        '@nodeValue' => 'Progressive',
                        '@position'  => 32,
                    ],
                    'Standard'                          => [
                        '@nodeValue' => 'PAL',
                        '@position'  => 33,
                    ],
                    'StreamOrder'                       => [
                        '@nodeValue' => 0,
                        '@position'  => 34,
                    ],
                    'StreamSize'                        => [
                        '@nodeValue' => 9318153,
                        '@position'  => 35,
                    ],
                    'Tagged_Date'                       => [
                        '@nodeValue' => 'UTC 2015-11-18 10:51:14',
                        '@position'  => 36,
                    ],
                    'transfer_characteristics'          => [
                        '@nodeValue' => 'BT.709',
                        '@position'  => 37,
                    ],
                    'transfer_characteristics_Source'   => [
                        '@nodeValue' => 'Stream',
                        '@position'  => 38,
                    ],
                    'Width'                             => [
                        '@nodeValue' => 1280,
                        '@position'  => 39,
                    ],
                    '@position'                         => 1,
                ],
                [
                    '@attributes'               => [
                        'type' => 'Audio',
                    ],
                    'BitRate'                   => [
                        '@nodeValue' => 159946,
                        '@position'  => 0,
                    ],
                    'BitRate_Maximum'           => [
                        '@nodeValue' => 188249,
                        '@position'  => 1,
                    ],
                    'BitRate_Mode'              => [
                        '@nodeValue' => 'VBR',
                        '@position'  => 2,
                    ],
                    'ChannelLayout'             => [
                        '@nodeValue' => 'L R',
                        '@position'  => 3,
                    ],
                    'ChannelPositions'          => [
                        '@nodeValue' => 'Front: L R',
                        '@position'  => 4,
                    ],
                    'Channels'                  => [
                        '@nodeValue' => 2,
                        '@position'  => 5,
                    ],
                    'CodecID'                   => [
                        '@nodeValue' => 'mp4a-40-2',
                        '@position'  => 6,
                    ],
                    'Compression_Mode'          => [
                        '@nodeValue' => 'Lossy',
                        '@position'  => 7,
                    ],
                    'Duration'                  => [
                        '@nodeValue' => 30.144,
                        '@position'  => 8,
                    ],
                    'Encoded_Date'              => [
                        '@nodeValue' => 'UTC 2015-11-18 10:51:14',
                        '@position'  => 9,
                    ],
                    'Format'                    => [
                        '@nodeValue' => 'AAC',
                        '@position'  => 10,
                    ],
                    'Format_AdditionalFeatures' => [
                        '@nodeValue' => 'LC',
                        '@position'  => 11,
                    ],
                    'FrameCount'                => [
                        '@nodeValue' => 1413,
                        '@position'  => 12,
                    ],
                    'FrameRate'                 => [
                        '@nodeValue' => 46.875,
                        '@position'  => 13,
                    ],
                    'ID'                        => [
                        '@nodeValue' => 2,
                        '@position'  => 14,
                    ],
                    'Language'                  => [
                        '@nodeValue' => 'en',
                        '@position'  => 15,
                    ],
                    'SamplesPerFrame'           => [
                        '@nodeValue' => 1024,
                        '@position'  => 16,
                    ],
                    'SamplingCount'             => [
                        '@nodeValue' => 1446912,
                        '@position'  => 17,
                    ],
                    'SamplingRate'              => [
                        '@nodeValue' => 48000,
                        '@position'  => 18,
                    ],
                    'StreamOrder'               => [
                        '@nodeValue' => 1,
                        '@position'  => 19,
                    ],
                    'StreamSize'                => [
                        '@nodeValue' => 602678,
                        '@position'  => 20,
                    ],
                    'StreamSize_Proportion'     => [
                        '@nodeValue' => 0.06065,
                        '@position'  => 21,
                    ],
                    'Tagged_Date'               => [
                        '@nodeValue' => 'UTC 2015-11-18 10:51:14',
                        '@position'  => 22,
                    ],
                    '@position'                 => 2,
                ],
            ],
            '@position'   => 1,
        ],
    ],
];
