<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\FamilyMember;
use App\Models\Master;
use App\Models\Member;
use App\Services\ImageUploader;

/**
 * Family members are sub-records attached to a primary member. They capture
 * the same personal details (including a photo) plus a family-member type.
 */
final class FamilyMemberController extends Controller
{
    public function create(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $member = $this->requireMember((int) $params['memberId'], $assocId);

        $this->view('family_members.form', [
            'title'        => 'Add Family Member',
            'member'       => $member,
            'familyMember' => null,
            'types'        => (new Master('family-member-types'))->activeForAssociation($assocId),
        ]);
        Session::clearFormState();
    }

    public function store(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $member = $this->requireMember((int) $params['memberId'], $assocId);

        $data = $this->validated($request, $assocId);
        $data['association_id'] = $assocId;
        $data['member_id'] = (int) $member['id'];

        $photo = $this->handlePhoto($request, (int) $member['id']);
        if ($photo !== null) {
            $data['photo_path'] = $photo;
        }

        (new FamilyMember())->create($data);
        $this->flash('success', 'Family member added.');
        $this->redirect('/members/' . $member['id']);
    }

    public function edit(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $family = (new FamilyMember())->findForAssociation((int) $params['id'], $assocId);
        if ($family === null) {
            Response::notFound();
        }
        $member = $this->requireMember((int) $family['member_id'], $assocId);

        $this->view('family_members.form', [
            'title'        => 'Edit Family Member',
            'member'       => $member,
            'familyMember' => $family,
            'types'        => (new Master('family-member-types'))->activeForAssociation($assocId),
        ]);
        Session::clearFormState();
    }

    public function update(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $family = (new FamilyMember())->findForAssociation((int) $params['id'], $assocId);
        if ($family === null) {
            Response::notFound();
        }

        $data = $this->validated($request, $assocId);

        $photo = $this->handlePhoto($request, (int) $family['member_id']);
        if ($photo !== null) {
            (new ImageUploader())->delete($family['photo_path'] ?? null);
            $data['photo_path'] = $photo;
        }

        (new FamilyMember())->update((int) $family['id'], $data);
        $this->flash('success', 'Family member updated.');
        $this->redirect('/members/' . $family['member_id']);
    }

    public function destroy(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $family = (new FamilyMember())->findForAssociation((int) $params['id'], $assocId);
        if ($family === null) {
            Response::notFound();
        }
        (new ImageUploader())->delete($family['photo_path'] ?? null);
        (new FamilyMember())->delete((int) $family['id']);
        $this->flash('success', 'Family member removed.');
        $this->redirect('/members/' . $family['member_id']);
    }

    /** @return array<string,mixed> */
    private function requireMember(int $memberId, int $assocId): array
    {
        $member = (new Member())->findForAssociation($memberId, $assocId);
        if ($member === null) {
            Response::notFound();
        }
        return $member;
    }

    /** @return array<string,mixed> */
    private function validated(Request $request, int $assocId): array
    {
        $input = [
            'family_member_type_id' => $request->input('family_member_type_id') ?: null,
            'name'       => (string) $request->input('name', ''),
            'age'        => $request->input('age'),
            'gender'     => (string) $request->input('gender', ''),
            'mobile'     => (string) $request->input('mobile', ''),
            'whatsapp'   => (string) $request->input('whatsapp', ''),
            'email'      => (string) $request->input('email', ''),
            'occupation' => (string) $request->input('occupation', ''),
            'relation'   => (string) $request->input('relation', ''),
            'notes'      => (string) $request->input('notes', ''),
        ];
        $validator = Validator::make($input, [
            'name'     => 'required|min:2|max:180',
            'age'      => 'integer|between:0,150',
            'gender'   => 'in:male,female,other',
            'mobile'   => 'phone',
            'whatsapp' => 'phone',
            'email'    => 'email|max:190',
            'notes'    => 'max:1000',
        ]);
        if ($validator->fails()) {
            $this->withErrors($validator->errors(), $input);
        }
        if ($input['family_member_type_id'] !== null
            && (new Master('family-member-types'))->findForAssociation((int) $input['family_member_type_id'], $assocId) === null) {
            $this->withErrors(['family_member_type_id' => 'Invalid family member type.'], $input);
        }

        // Normalise optional/empty values.
        $input['age'] = $input['age'] !== '' && $input['age'] !== null ? (int) $input['age'] : null;
        foreach (['gender', 'mobile', 'whatsapp', 'email', 'occupation', 'relation', 'notes'] as $k) {
            $input[$k] = $input[$k] !== '' && $input[$k] !== null ? $input[$k] : null;
        }
        return $input;
    }

    private function handlePhoto(Request $request, int $memberId): ?string
    {
        $file = $request->file('photo');
        if ($file === null) {
            return null;
        }
        try {
            return (new ImageUploader())->store($file, 'family');
        } catch (\RuntimeException $e) {
            $this->withErrors(['photo' => $e->getMessage()]);
        }
        return null;
    }
}
