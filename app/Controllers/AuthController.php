<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Logger;
use App\Core\Mailer;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Association;
use App\Models\LoginAttempt;
use App\Models\PasswordReset;
use App\Models\User;

final class AuthController extends Controller
{
    public function showLogin(Request $request): void
    {
        if (Auth::check()) {
            $this->redirect($this->homeFor(Auth::role()));
        }
        $this->view('auth.login', ['title' => 'Sign in']);
        Session::clearFormState();
    }

    public function login(Request $request): void
    {
        $email = (string) $request->input('email', '');
        $password = (string) $request->input('password', '');
        $ip = $request->ip();

        $validator = Validator::make(
            ['email' => $email, 'password' => $password],
            ['email' => 'required|email|max:190', 'password' => 'required|max:255']
        );
        if ($validator->fails()) {
            $this->withErrors($validator->errors(), ['email' => $email]);
        }

        $attempts = new LoginAttempt();
        $security = $this->config('security');
        if ($attempts->recentFailures($email, $ip, (int) $security['login_decay_minutes']) >= (int) $security['login_max_attempts']) {
            Logger::warning('Login throttled', ['email' => $email, 'ip' => $ip]);
            $this->withErrors(
                ['email' => 'Too many failed attempts. Please wait a few minutes and try again.'],
                ['email' => $email]
            );
        }

        $user = Auth::attempt($email, $password);
        if ($user === null) {
            $attempts->record($email, $ip, false);
            $this->withErrors(['email' => 'These credentials do not match our records.'], ['email' => $email]);
        }

        // Subscription / association-active enforcement (non super admins).
        if ($user['role'] !== 'super_admin' && $user['association_id'] !== null) {
            $association = (new Association())->find((int) $user['association_id']);
            if ($association === null || !(new Association())->subscriptionActive($association)) {
                $attempts->record($email, $ip, false);
                $this->withErrors(
                    ['email' => 'Your association subscription is inactive or has expired. Please contact your administrator.'],
                    ['email' => $email]
                );
            }
        }

        $attempts->record($email, $ip, true);
        $attempts->clearFor($email, $ip);
        Auth::login($user);
        Session::clearFormState();

        if (Auth::mustChangePassword()) {
            $this->redirect('/password/force-change');
        }
        $this->flash('success', 'Welcome back, ' . $user['name'] . '.');
        $this->redirect($this->homeFor($user['role']));
    }

    public function logout(Request $request): void
    {
        Auth::logout();
        Session::flash('success', 'You have been signed out.');
        $this->redirect('/login');
    }

    // ---- Forgot password ------------------------------------------------

    public function showForgot(Request $request): void
    {
        $this->view('auth.forgot', ['title' => 'Forgot password']);
        Session::clearFormState();
    }

    public function sendReset(Request $request): void
    {
        $email = (string) $request->input('email', '');
        $ip = $request->ip();

        $validator = Validator::make(['email' => $email], ['email' => 'required|email|max:190']);
        if ($validator->fails()) {
            $this->withErrors($validator->errors(), ['email' => $email]);
        }

        // Throttle reset requests too.
        $attempts = new LoginAttempt();
        $security = $this->config('security');
        if ($attempts->recentFailures('reset:' . $email, $ip, (int) $security['login_decay_minutes']) >= (int) $security['login_max_attempts']) {
            $this->neutralResetResponse();
        }
        $attempts->record('reset:' . $email, $ip, false);

        $user = (new User())->findByEmail($email);
        if ($user !== null && (int) $user['is_active'] === 1) {
            $ttl = (int) $security['reset_token_ttl'];
            $token = (new PasswordReset())->issue((int) $user['id'], $ttl);
            $link = abs_url('/password/reset?token=' . $token);
            $this->sendResetEmail($user, $link, $ttl);
        }

        $this->neutralResetResponse();
    }

    private function neutralResetResponse(): never
    {
        // Always neutral to prevent account enumeration.
        Session::flash('success', 'If that email matches an account, a password reset link has been sent.');
        $this->redirect('/login');
    }

    private function sendResetEmail(array $user, string $link, int $ttl): void
    {
        $appName = (string) $this->config('app.name');
        $html = View_reset_email($appName, $user['name'], $link, $ttl);
        (new Mailer())->send($user['email'], $user['name'], "{$appName} — Password reset", $html);
    }

    public function showReset(Request $request): void
    {
        $token = (string) $request->input('token', '');
        $reset = $token !== '' ? (new PasswordReset())->findValid($token) : null;
        if ($reset === null) {
            Session::flash('error', 'This password reset link is invalid or has expired.');
            $this->redirect('/password/forgot');
        }
        $this->view('auth.reset', ['title' => 'Reset password', 'token' => $token]);
        Session::clearFormState();
    }

    public function doReset(Request $request): void
    {
        $token = (string) $request->input('token', '');
        $password = (string) $request->input('password', '');

        $reset = $token !== '' ? (new PasswordReset())->findValid($token) : null;
        if ($reset === null) {
            Session::flash('error', 'This password reset link is invalid or has expired.');
            $this->redirect('/password/forgot');
        }

        $validator = Validator::make(
            ['password' => $password, 'password_confirmation' => $request->input('password_confirmation')],
            ['password' => 'required|min:8|max:255|confirmed']
        );
        if ($validator->fails()) {
            Session::flashErrors($validator->errors());
            $this->redirect('/password/reset?token=' . urlencode($token));
        }

        $userModel = new User();
        $userModel->updatePassword((int) $reset['user_id'], $password);
        (new PasswordReset())->markUsed((int) $reset['id']);

        Session::flash('success', 'Your password has been reset. You can now sign in.');
        $this->redirect('/login');
    }

    // ---- Forced password change (first login) --------------------------

    public function showForceChange(Request $request): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }
        $this->view('auth.force_change', ['title' => 'Change your password']);
        Session::clearFormState();
    }

    public function doForceChange(Request $request): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }
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
        Session::flash('success', 'Your password has been updated.');
        $this->redirect($this->homeFor($user['role']));
    }

    private function homeFor(?string $role): string
    {
        return match ($role) {
            'super_admin' => '/admin/associations',
            'member'      => '/member/profile',
            default       => '/dashboard',
        };
    }
}

/**
 * Small standalone renderer for the reset email body (kept near the controller
 * to avoid a separate view include from a non-request context).
 */
function View_reset_email(string $appName, string $name, string $link, int $ttl): string
{
    $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $safeLink = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
    return <<<HTML
    <div style="font-family:Arial,sans-serif;color:#1f2937;line-height:1.6">
        <h2 style="color:#047857">{$appName}</h2>
        <p>Hello {$safeName},</p>
        <p>We received a request to reset your password. Click the button below to choose a new one. This link expires in {$ttl} minutes and can be used once.</p>
        <p style="margin:24px 0">
            <a href="{$safeLink}" style="background:#047857;color:#fff;padding:12px 20px;border-radius:8px;text-decoration:none">Reset password</a>
        </p>
        <p>If you did not request this, you can safely ignore this email.</p>
        <p style="color:#6b7280;font-size:12px">{$safeLink}</p>
    </div>
    HTML;
}
