<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Committee;
use App\Models\CommitteeOfficial;
use App\Models\Master;
use App\Models\User;
use App\Services\ImageUploader;

/**
 * Committee officials (sub-records of a committee). An official may optionally
 * be given a login (association admin or staff role).
 */
final class CommitteeOfficialController extends Controller
{
    public function create(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $committee = $this->requireCommittee((int) $params['committeeId'], $assocId);

        $this->view('officials.form', [
            'title'       => 'Add Official',
            'committee'   => $committee,
            'official'    => null,
            'designations' => (new Master('official-designations'))->activeForAssociation($assocId),
        ]);
        Session::clearFormState();
    }

    public function store(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $committee = $this->requireCommittee((int) $params['committeeId'], $assocId);

        $data = $this->validated($request, $assocId, null);
        $data['association_id'] = $assocId;
        $data['committee_id'] = (int) $committee['id'];

        $photo = $this->handlePhoto($request);
        if ($photo !== null) {
            $data['photo_path'] = $photo;
        }

        // Optionally create a login for this official.
        $userId = $this->maybeCreateLogin($request, $assocId, $data['name'], $data['email'] ?? null, null);
        if ($userId !== null) {
            $data['user_id'] = $userId;
        }

        (new CommitteeOfficial())->create($data);
        $this->flash('success', 'Official added.' . ($userId !== null ? ' Login created.' : ''));
        $this->redirect('/committees/' . $committee['id']);
    }

    public function edit(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $official = (new CommitteeOfficial())->findForAssociation((int) $params['id'], $assocId);
        if ($official === null) {
            Response::notFound();
        }
        $committee = $this->requireCommittee((int) $official['committee_id'], $assocId);
        $login = $official['user_id'] ? (new User())->find((int) $official['user_id']) : null;

        $this->view('officials.form', [
            'title'        => 'Edit Official',
            'committee'    => $committee,
            'official'     => $official,
            'login'        => $login,
            'designations' => (new Master('official-designations'))->activeForAssociation($assocId),
        ]);
        Session::clearFormState();
    }

    public function update(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $official = (new CommitteeOfficial())->findForAssociation((int) $params['id'], $assocId);
        if ($official === null) {
            Response::notFound();
        }

        $data = $this->validated($request, $assocId, (int) $official['id']);

        $photo = $this->handlePhoto($request);
        if ($photo !== null) {
            (new ImageUploader())->delete($official['photo_path'] ?? null);
            $data['photo_path'] = $photo;
        }

        // Manage the login: create if requested and none exists; otherwise
        // update role / reset password on the existing account.
        $existingUserId = $official['user_id'] ? (int) $official['user_id'] : null;
        $userId = $this->maybeCreateLogin($request, $assocId, $data['name'], $data['email'] ?? null, $existingUserId);
        if ($userId !== null) {
            $data['user_id'] = $userId;
        }

        (new CommitteeOfficial())->update((int) $official['id'], $data);
        $this->flash('success', 'Official updated.');
        $this->redirect('/committees/' . $official['committee_id']);
    }

    public function destroy(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $official = (new CommitteeOfficial())->findForAssociation((int) $params['id'], $assocId);
        if ($official === null) {
            Response::notFound();
        }
        (new ImageUploader())->delete($official['photo_path'] ?? null);
        // Deactivate any linked login rather than deleting (preserves history).
        if ($official['user_id']) {
            (new User())->update((int) $official['user_id'], ['is_active' => 0]);
        }
        (new CommitteeOfficial())->delete((int) $official['id']);
        $this->flash('success', 'Official removed.');
        $this->redirect('/committees/' . $official['committee_id']);
    }

    /** @return array<string,mixed> */
    private function requireCommittee(int $committeeId, int $assocId): array
    {
        $committee = (new Committee())->findForAssociation($committeeId, $assocId);
        if ($committee === null) {
            Response::notFound();
        }
        return $committee;
    }

    /**
     * Create a login (or update role/password on an existing one) when the
     * "create login" box is ticked. Returns the user id to store, or null.
     */
    private function maybeCreateLogin(Request $request, int $assocId, string $name, ?string $email, ?int $existingUserId): ?int
    {
        $wantsLogin = (string) $request->input('create_login', '') !== '';
        $role = $request->input('login_role') === 'association_admin' ? 'association_admin' : 'association_staff';
        $password = (string) $request->input('login_password', '');
        $userModel = new User();

        // Existing login: update role, and reset password if one was supplied.
        if ($existingUserId !== null) {
            $userModel->update($existingUserId, ['role' => $role, 'name' => $name]);
            if ($password !== '') {
                if (strlen($password) < 8) {
                    $this->withErrors(['login_password' => 'Password must be at least 8 characters.']);
                }
                $userModel->updatePassword($existingUserId, $password, false);
            }
            return $existingUserId;
        }

        if (!$wantsLogin) {
            return null;
        }
        // New login requires a valid email + password.
        if ($email === null || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->withErrors(['email' => 'A valid email is required to create a login.']);
        }
        if (strlen($password) < 8) {
            $this->withErrors(['login_password' => 'Password must be at least 8 characters.']);
        }
        if ($userModel->emailExists($email)) {
            $this->withErrors(['email' => 'A user with this email already exists.']);
        }

        return $userModel->createUser([
            'association_id' => $assocId,
            'name'           => $name,
            'email'          => $email,
            'role'           => $role,
            'is_active'      => 1,
            'must_change_password' => 1,
        ], $password);
    }

    /** @return array<string,mixed> */
    private function validated(Request $request, int $assocId, ?int $ignoreId): array
    {
        $input = [
            'official_designation_id' => $request->input('official_designation_id') ?: null,
            'name'    => (string) $request->input('name', ''),
            'phone'   => (string) $request->input('phone', ''),
            'email'   => (string) $request->input('email', ''),
            'address' => (string) $request->input('address', ''),
        ];
        $validator = Validator::make($input, [
            'name'    => 'required|min:2|max:180',
            'phone'   => 'phone',
            'email'   => 'email|max:190',
            'address' => 'max:500',
        ]);
        if ($validator->fails()) {
            $this->withErrors($validator->errors(), $input);
        }
        if ($input['official_designation_id'] !== null
            && (new Master('official-designations'))->findForAssociation((int) $input['official_designation_id'], $assocId) === null) {
            $this->withErrors(['official_designation_id' => 'Invalid designation.'], $input);
        }
        foreach (['phone', 'email', 'address'] as $k) {
            $input[$k] = $input[$k] !== '' ? $input[$k] : null;
        }
        return $input;
    }

    private function handlePhoto(Request $request): ?string
    {
        $file = $request->file('photo');
        if ($file === null) {
            return null;
        }
        try {
            return (new ImageUploader())->store($file, 'officials');
        } catch (\RuntimeException $e) {
            $this->withErrors(['photo' => $e->getMessage()]);
        }
        return null;
    }
}
