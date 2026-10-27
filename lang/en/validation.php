<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Artwork Image Upload Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Server-side security validation messages for the admin artwork upload
    | form (App\Rules\ValidArtworkImage). These keys are merged over the
    | framework's default validation lines; add a matching file under
    | lang/{locale}/ to translate them into another language.
    |
    */

    'artwork_image' => [
        'not_a_file' => 'The :attribute must be a file upload.',
        'upload_failed' => 'The :attribute upload failed. Please try again.',
        'too_large' => 'The :attribute may not be greater than :max megabytes.',
        'bad_extension' => 'The :attribute must end in one of these extensions: .:extensions.',
        'unreadable' => 'The :attribute file could not be read.',
        'not_image' => 'The :attribute must be a JPEG, PNG, WebP, or GIF image.',
        'extension_mismatch' => 'The :attribute extension ".:extension" does not match the actual image format (:mime).',
        'corrupt' => 'The :attribute must be a valid, uncorrupted image file.',
        'dimensions_exceeded' => 'The :attribute dimensions may not exceed :max_dimension pixels per side.',
        'type_mismatch' => 'The :attribute image data does not match its detected file type.',
    ],

];
