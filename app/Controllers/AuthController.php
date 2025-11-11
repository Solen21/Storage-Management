<?php

namespace App\Controllers;

use App\Core\Controller;

class AuthController extends Controller
{
    public function login()
    {
        $this->view('auth/login');
    }
}
