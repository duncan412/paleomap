<?php

declare(strict_types=1);

use App\Http\Controllers\Api\FossilController;
use Illuminate\Support\Facades\Route;

Route::get('/fossils', [FossilController::class, 'index'])
    ->name('api.fossils.index');

Route::get('/fossils/{fossil}', [FossilController::class, 'show'])
    ->whereUuid('fossil')
    ->name('api.fossils.show');
