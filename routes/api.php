<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\QuestionGeneratorController;
use Illuminate\Support\Facades\DB;

Route::post('/generate-questions/pdf', [QuestionGeneratorController::class, 'generateFromPDF']);
Route::post('/generate-questions/notes', [QuestionGeneratorController::class, 'generateFromNotes']);

Route::get('/notes', function () {
    $notes = DB::table('notes')->select('id', 'title', 'created_at')->get();
    return response()->json(['notes' => $notes]);
});