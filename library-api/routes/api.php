<?php

use App\Http\Controllers\LoanController;
use App\Http\Controllers\MemberController;
use Illuminate\Support\Facades\Route;

Route::get('/members', [MemberController::class, 'index']);
Route::get('/members/{memberId}', [MemberController::class, 'show']);

Route::post('/members/{memberId}/loans', [LoanController::class, 'store']);
Route::delete('/members/{memberId}/loans/{copyId}', [LoanController::class, 'destroy']);
