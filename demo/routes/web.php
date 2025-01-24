<?php

use Illuminate\Support\Facades\Route;

use App\Models\SingleImageExample;

Route::get('/', function () {
    $singleImageExamples = SingleImageExample::all();

    return view('welcome', compact('singleImageExamples'));
});
