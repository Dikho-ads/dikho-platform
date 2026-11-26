<?php

use Illuminate\Support\Facades\Route;

// The Vue SPA owns every page. API routes arrive with the backend phase.
Route::view('/{any?}', 'app')->where('any', '^(?!api).*$');
