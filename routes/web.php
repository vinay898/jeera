<?php

use App\Http\Controllers\AcceptInvitationController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

// Team invitations
Route::get('/invitations/{token}/accept', [AcceptInvitationController::class, 'show'])
    ->name('invitations.accept');

Route::get('/invitations/process', [AcceptInvitationController::class, 'accept'])
    ->middleware('auth')
    ->name('invitations.accept.process');
