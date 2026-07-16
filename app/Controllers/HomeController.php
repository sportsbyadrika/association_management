<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;

final class HomeController extends Controller
{
    public function index(Request $request): void
    {
        $this->view('public.home', ['title' => 'Membership management for Associations']);
    }

    /**
     * Post-login landing dispatcher, sends each role to its home.
     */
    public function redirectHome(Request $request): void
    {
        $this->redirect(match (Auth::role()) {
            'super_admin' => '/admin/associations',
            'member'      => '/member/profile',
            default       => '/dashboard',
        });
    }
}
