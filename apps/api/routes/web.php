<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect('/api/documentation'));
Route::get('/api/documentation', fn() => response()->file(public_path('docs.html')));
Route::get('/swagger.yaml', fn() => response()->file(public_path('swagger.yaml'))->header('Content-Type', 'text/yaml'));
