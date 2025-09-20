<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\ExpenseController;

// Groups
Route::get('/groups', [GroupController::class, 'index']);
Route::post('/groups', [GroupController::class, 'store']);
Route::get('/groups/{group}', [GroupController::class, 'show']);
Route::delete('/groups/{group}', [GroupController::class, 'destroy']);

// Members (scoped to group)
Route::post('/groups/{group}/members', [MemberController::class, 'store']);
Route::delete('/members/{member}', [MemberController::class, 'destroy']);

// Expenses (scoped to group)
Route::get('/groups/{group}/expenses', [ExpenseController::class, 'index']);
Route::post('/groups/{group}/expenses', [ExpenseController::class, 'store']);
Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy']);

// Balances
Route::get('/groups/{group}/balances', [GroupController::class, 'balances']);
