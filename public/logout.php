<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap/app.php';

use App\Auth;

Auth::logout();
flash('success', 'Вы вышли из аккаунта.');
redirect(route('home'));
