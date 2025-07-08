<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('tin-nhan.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
