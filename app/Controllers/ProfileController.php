<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Models\User;

final class ProfileController extends Controller
{
    public function show(Request $request): void
    {
        $this->view('profile.show', ['title' => 'My Profile', 'user' => Auth::user()]);
        Session::clearFormState();
    }

    public function update(Request $request): void
    {
        $user = Auth::user();
        $name = (string) $request->input('name', '');

        $validator = Validator::make(['name' => $name], ['name' => 'required|min:2|max:180']);
        if ($validator->fails()) {
            $this->withErrors($validator->errors(), ['name' => $name]);
        }

        (new User())->update((int) $user['id'], ['name' => $name]);
        $this->flash('success', 'Your profile has been updated.');
        $this->redirect('/profile');
    }

    public function showPassword(Request $request): void
    {
        $this->view('profile.password', ['title' => 'Change Password']);
        Session::clearFormState();
    }

    public function updatePassword(Request $request): void
    {
        $user = Auth::user();
        $current = (string) $request->input('current_password', '');
        $password = (string) $request->input('password', '');

        $validator = Validator::make(
            [
                'current_password' => $current,
                'password' => $password,
                'password_confirmation' => $request->input('password_confirmation'),
            ],
            [
                'current_password' => 'required',
                'password' => 'required|min:8|max:255|confirmed',
            ]
        );
        if ($validator->fails()) {
            $this->withErrors($validator->errors());
        }

        if (!password_verify($current, $user['password_hash'])) {
            $this->withErrors(['current_password' => 'Your current password is incorrect.']);
        }

        (new User())->updatePassword((int) $user['id'], $password);
        $this->flash('success', 'Your password has been changed.');
        $this->redirect('/profile');
    }
}
