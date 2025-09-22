<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    '@symfony/stimulus-bundle' => [
        'path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js',
    ],
    '@hotwired/turbo' => [
        'version' => '7.3.0',
    ],

    // JS
    'admin-profile-actions' => [
        'path' => './assets/js/admin-profile-actions.js',
        'entrypoint' => true,
    ],
    'character-counter' => [
        'path' => './assets/js/character-counter.js',
        'entrypoint' => true,
    ],
    'drag-scroll' => [
        'path' => './assets/js/drag-scroll.js',
        'entrypoint' => true,
    ],
    'form-submit' => [
        'path' => './assets/js/form-submit.js',
        'entrypoint' => true,
    ],
    'image-modal' => [
        'path' => './assets/js/image-modal.js',
        'entrypoint' => true,
    ],
    'image-preview' => [
        'path' => './assets/js/image-preview.js',
        'entrypoint' => true,
    ],
    'profile-picture-upload' => [
        'path' => './assets/js/profile-picture-upload.js',
        'entrypoint' => true,
    ],
];
