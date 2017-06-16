<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API settings to access Gitlab server
    |--------------------------------------------------------------------------
    |
    */

    'api' => [
        'url' => env('GITLAB_API_URL','https://gitlab.com/api/v4/'),
        'token' => env('GITLAB_API_TOKEN', 'your_token'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default settings
    |--------------------------------------------------------------------------
    |
    */

    'default' => [
        'project' => [
            'id' => env('GITLAB_DEFAULT_PROJECT_ID', ''),
        ],
        'group' => [
            'id' => env('GITLAB_DEFAULT_GROUP_ID', ''),
        ],
        // MERGE REQUEST
        'mr' => [
            'target-branch' => env('GITLAB_DEFAULT_MR_TARGET_BRANCH','dev'),
        ],
    ],

];
