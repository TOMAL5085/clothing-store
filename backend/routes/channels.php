<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Private channels carry real-time notification delivery. Every callback
| authorizes against the authenticated user only — the frontend can never
| subscribe as another user, and guests are rejected by the sanctum
| middleware on the broadcast auth route before these run.
|
*/

// Each customer receives their own notifications on their private channel.
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return $user !== null && (int) $user->id === (int) $id;
});

// All admins share one private channel restricted to active admins.
Broadcast::channel('admin.notifications', function ($user) {
    return $user !== null && $user->isAdmin() && $user->isActive();
});
