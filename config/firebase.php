<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Firebase Service Account
    |--------------------------------------------------------------------------
    |
    | The service-account JSON must remain outside the public directory.
    |
    */

    'credentials' => storage_path(
        'app/private/firebase/service-account.json'
    ),

    /*
    |--------------------------------------------------------------------------
    | OAuth Scope
    |--------------------------------------------------------------------------
    */

    'scope' =>
        'https://www.googleapis.com/auth/firebase.messaging',

];