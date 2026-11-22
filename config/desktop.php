<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SQLCipher key (optional)
    |--------------------------------------------------------------------------
    |
    | Used only when the bundled PHP SQLite is compiled with SQLCipher.
    | Until then, Electron seals database.sealed at rest and unlocks a
    | runtime/database.sqlite copy while Kipit Desktop is running.
    |
    */
    'sqlcipher_key' => env('DESKTOP_SQLCIPHER_KEY'),

];
