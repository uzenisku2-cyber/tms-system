<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

Route::get('/debug/traces', function () {
    return view('debug.traces');
});

Route::get('/debug/traces/{id}', function ($id) {
    $traces = DB::table('traces')
        ->where('trace_id', $id)
        ->orderBy('id')
        ->get();

    return view('debug.trace-detail', compact('traces', 'id'));
});