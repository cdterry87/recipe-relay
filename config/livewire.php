<?php

/*
| Only the settings Recipe Relay changes; Livewire's own config file supplies the rest.
*/

return [

    'temporary_file_upload' => [
        'disk' => env('LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK'),
        'rules' => null,
        'directory' => null,
        // The demo account can't upload files at all (it picks from the seed photos instead).
        'middleware' => ['throttle:60,1', 'not-demo'],
        'preview_mimes' => [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
            'mov', 'avi', 'wmv', 'mp3', 'm4a',
            'jpg', 'jpeg', 'mpga', 'webp', 'wma',
        ],
        'max_upload_time' => 5,
        'cleanup' => true,
    ],

];
