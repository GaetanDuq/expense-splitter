<?php

use Illuminate\Support\Facades\Route;

// Send everything EXCEPT /api/* to the Vue SPA
Route::view('/{any?}', 'app')->where('any', '^(?!api).*$');
