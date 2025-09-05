<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HelloController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/ping', fn() => "Hello World");
Route::get("/json", fn() => response()->json(["message" => "Hello World"]));

Route::get('/hello/{name?}', [HelloController::class, 'show'])->name('hello');


