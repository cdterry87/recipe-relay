<?php

/*
| Only the settings Recipe Relay changes; Livewire's own config file supplies the rest.
*/

return [

    'temporary_file_upload' => [
        'disk' => env('LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK'),
        // Only photos are ever uploaded, so nothing else reaches the temporary folder.
        'rules' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:8192'],
        'directory' => null,
        // The demo account can't upload files at all (it picks from the seed photos instead).
        'middleware' => ['throttle:60,1', 'not-demo'],
        // Raster images only: an SVG preview could carry script.
        'preview_mimes' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'max_upload_time' => 5,
        'cleanup' => true,
    ],

];
