<?php

return [
    'default_disk' => 'public',

    'allowed_disks' => [
        'public',
    ],

    'max_upload_mb' => (int) env('MEDIA_UPLOAD_MAX_MB', 5),

    'default_folder_id' => (int) env('MEDIA_DEFAULT_FOLDER_ID', 2),

    'size_presets' => [
        'square' => [
            'label' => 'Square',
            'width' => 800,
            'height' => 800,
        ],
        'banner' => [
            'label' => 'Banner',
            'width' => 1200,
            'height' => 400,
        ],
    ],
];
