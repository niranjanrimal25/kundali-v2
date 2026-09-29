<?php

use App\Livewire\Kundali\CreateKundali;
use App\Livewire\Kundali\EditKundali;
use App\Livewire\Kundali\FullReading;
use App\Livewire\Kundali\KundaliIndex;
use App\Livewire\Kundali\ShowKundali;
use Illuminate\Support\Facades\Route;

// Guests get the landing page; signed-in users go straight to work.
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : view('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::get('kundalis', KundaliIndex::class)->name('kundalis.index');
    Route::get('kundalis/create', CreateKundali::class)->name('kundalis.create');
    Route::get('kundalis/{kundali}/edit', EditKundali::class)->name('kundalis.edit');
    Route::get('kundalis/{kundali}', ShowKundali::class)->name('kundalis.show');
    Route::get('kundalis/{kundali}/reading', FullReading::class)->name('kundalis.reading');

    Route::view('profile', 'profile')->name('profile');
});

require __DIR__.'/auth.php';
