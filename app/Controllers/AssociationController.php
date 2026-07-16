<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Helpers\Defaults;
use App\Models\Association;
use App\Models\User;
use App\Services\ImageUploader;

/**
 * Super Admin: manage associations and their admin accounts.
 */
final class AssociationController extends Controller
{
    public function index(Request $request): void
    {
        $associations = (new Association())->all('created_at DESC');
        $model = new Association();
        foreach ($associations as &$a) {
            $a['sub_active'] = $model->subscriptionActive($a);
        }
        unset($a);
        $this->view('admin.associations.index', [
            'title' => 'Associations',
            'associations' => $associations,
        ]);
        Session::clearFormState();
    }

    public function create(Request $request): void
    {
        $this->view('admin.associations.form', ['title' => 'Add Association', 'association' => null]);
        Session::clearFormState();
    }

    public function store(Request $request): void
    {
        $data = $this->validated($request);

        $logoPath = $this->handleLogo($request);
        if ($logoPath !== null) {
            $data['logo_path'] = $logoPath;
        }

        $db = Database::instance();
        $id = $db->transaction(function () use ($db, $data): int {
            $model = new Association();
            $newId = $model->create($data);
            Defaults::seedMasters($db, $newId); // seed default masters
            return $newId;
        });

        $this->flash('success', 'Association created with default master data.');
        $this->redirect('/admin/associations');
    }

    public function edit(Request $request, array $params): void
    {
        $association = (new Association())->find((int) $params['id']);
        if ($association === null) {
            Response::notFound();
        }
        $this->view('admin.associations.form', ['title' => 'Edit Association', 'association' => $association]);
        Session::clearFormState();
    }

    public function update(Request $request, array $params): void
    {
        $id = (int) $params['id'];
        $association = (new Association())->find($id);
        if ($association === null) {
            Response::notFound();
        }

        $data = $this->validated($request);
        $logoPath = $this->handleLogo($request);
        if ($logoPath !== null) {
            (new ImageUploader())->delete($association['logo_path'] ?? null);
            $data['logo_path'] = $logoPath;
        }

        (new Association())->update($id, $data);
        $this->flash('success', 'Association updated.');
        $this->redirect('/admin/associations');
    }

    /** @return array<string,mixed> */
    private function validated(Request $request): array
    {
        $input = [
            'name'          => (string) $request->input('name', ''),
            'contact_email' => (string) $request->input('contact_email', ''),
            'contact_phone' => (string) $request->input('contact_phone', ''),
            'address'       => (string) $request->input('address', ''),
            'subscription_start' => (string) $request->input('subscription_start', ''),
            'subscription_end'   => (string) $request->input('subscription_end', ''),
            'is_active'     => $request->input('is_active') ? 1 : 0,
        ];
        $validator = Validator::make($input, [
            'name'          => 'required|min:2|max:180',
            'contact_email' => 'email|max:180',
            'contact_phone' => 'phone',
            'address'       => 'max:500',
            'subscription_start' => 'date',
            'subscription_end'   => 'date',
        ]);
        if ($validator->fails()) {
            $this->withErrors($validator->errors(), $input);
        }
        $input['subscription_start'] = $input['subscription_start'] ?: null;
        $input['subscription_end'] = $input['subscription_end'] ?: null;
        $input['contact_email'] = $input['contact_email'] ?: null;
        return $input;
    }

    private function handleLogo(Request $request): ?string
    {
        $file = $request->file('logo');
        if ($file === null) {
            return null;
        }
        try {
            return (new ImageUploader())->store($file, 'associations');
        } catch (\RuntimeException $e) {
            $this->withErrors(['logo' => $e->getMessage()]);
        }
    }

    // ---- Admin accounts -------------------------------------------------

    public function admins(Request $request): void
    {
        $admins = (new User())->associationAdmins();
        $associations = (new Association())->all('name ASC');
        $this->view('admin.admins.index', [
            'title' => 'Association Admins',
            'admins' => $admins,
            'associations' => $associations,
        ]);
        Session::clearFormState();
    }

    public function createAdmin(Request $request, array $params): void
    {
        $association = (new Association())->find((int) $params['id']);
        if ($association === null) {
            Response::notFound();
        }
        $this->view('admin.admins.form', [
            'title' => 'Add Admin',
            'association' => $association,
            'admin' => null,
        ]);
        Session::clearFormState();
    }

    public function storeAdmin(Request $request, array $params): void
    {
        $association = (new Association())->find((int) $params['id']);
        if ($association === null) {
            Response::notFound();
        }

        $input = [
            'name'  => (string) $request->input('name', ''),
            'email' => strtolower((string) $request->input('email', '')),
            'password' => (string) $request->input('password', ''),
            'role'  => $request->input('role') === 'association_staff' ? 'association_staff' : 'association_admin',
        ];
        $validator = Validator::make($input, [
            'name'  => 'required|min:2|max:180',
            'email' => 'required|email|max:190',
            'password' => 'required|min:8|max:255',
        ]);
        if ($validator->fails()) {
            $this->withErrors($validator->errors(), $input);
        }
        if ((new User())->emailExists($input['email'])) {
            $this->withErrors(['email' => 'That email is already registered.'], $input);
        }

        (new User())->createUser([
            'association_id' => (int) $association['id'],
            'name'  => $input['name'],
            'email' => $input['email'],
            'role'  => $input['role'],
            'is_active' => 1,
            'must_change_password' => 1,
        ], $input['password']);

        $this->flash('success', 'Admin account created. They must change their password on first login.');
        $this->redirect('/admin/admins');
    }

    public function editAdmin(Request $request, array $params): void
    {
        $admin = (new User())->find((int) $params['id']);
        if ($admin === null || !in_array($admin['role'], ['association_admin', 'association_staff'], true)) {
            Response::notFound();
        }
        $association = (new Association())->find((int) $admin['association_id']);
        $this->view('admin.admins.form', [
            'title' => 'Edit Admin',
            'association' => $association,
            'admin' => $admin,
        ]);
        Session::clearFormState();
    }

    public function updateAdmin(Request $request, array $params): void
    {
        $admin = (new User())->find((int) $params['id']);
        if ($admin === null || !in_array($admin['role'], ['association_admin', 'association_staff'], true)) {
            Response::notFound();
        }

        $input = [
            'name'  => (string) $request->input('name', ''),
            'email' => strtolower((string) $request->input('email', '')),
            'is_active' => $request->input('is_active') ? 1 : 0,
            'role'  => $request->input('role') === 'association_staff' ? 'association_staff' : 'association_admin',
        ];
        $validator = Validator::make($input, [
            'name'  => 'required|min:2|max:180',
            'email' => 'required|email|max:190',
        ]);
        if ($validator->fails()) {
            $this->withErrors($validator->errors(), $input);
        }
        if ((new User())->emailExists($input['email'], (int) $admin['id'])) {
            $this->withErrors(['email' => 'That email is already registered.'], $input);
        }

        (new User())->update((int) $admin['id'], $input);
        $this->flash('success', 'Admin account updated.');
        $this->redirect('/admin/admins');
    }

    public function resetAdminPassword(Request $request, array $params): void
    {
        $admin = (new User())->find((int) $params['id']);
        if ($admin === null || !in_array($admin['role'], ['association_admin', 'association_staff'], true)) {
            Response::notFound();
        }

        $password = (string) $request->input('password', '');
        $validator = Validator::make(['password' => $password], ['password' => 'required|min:8|max:255']);
        if ($validator->fails()) {
            $this->withErrors($validator->errors());
        }

        (new User())->updatePassword((int) $admin['id'], $password, false);
        // Force change on next login.
        (new User())->update((int) $admin['id'], ['must_change_password' => 1]);
        $this->flash('success', 'Password reset. The user must change it on next login.');
        $this->redirect('/admin/admins');
    }
}
