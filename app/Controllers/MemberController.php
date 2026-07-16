<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Master;
use App\Models\Member;
use App\Services\ImageUploader;
use App\Services\MemberLedger;

final class MemberController extends Controller
{
    public function index(Request $request): void
    {
        $assocId = Auth::associationId();
        $search = trim((string) $request->input('q', ''));
        $sort = (string) $request->input('sort', 'name');
        $dir = (string) $request->input('dir', 'asc');
        $page = (int) $request->input('page', 1);

        $result = (new Member())->paginateForAssociation($assocId, $search, $sort, $dir, $page, 15);

        $this->view('members.index', [
            'title'    => 'Members',
            'members'  => $result['data'],
            'paginator' => $result,
            'search'   => $search,
            'sort'     => $sort,
            'dir'      => $dir,
        ]);
        Session::clearFormState();
    }

    public function create(Request $request): void
    {
        $this->view('members.form', [
            'title'  => 'Add Member',
            'member' => null,
            'memberTypes' => $this->memberTypes(),
        ]);
        Session::clearFormState();
    }

    public function store(Request $request): void
    {
        $assocId = Auth::associationId();
        $data = $this->validated($request);
        $data['association_id'] = $assocId;

        $photo = $this->handlePhoto($request);
        if ($photo !== null) {
            $data['photo_path'] = $photo;
        }

        $id = (new Member())->create($data);
        $this->flash('success', 'Member added.');
        $this->redirect('/members/' . $id);
    }

    public function show(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $member = (new Member())->findWithType((int) $params['id'], $assocId);
        if ($member === null) {
            Response::notFound();
        }
        $ledger = (new MemberLedger())->build((int) $member['id']);
        $this->view('members.show', [
            'title'  => $member['name'],
            'member' => $member,
            'ledger' => $ledger,
        ]);
    }

    public function edit(Request $request, array $params): void
    {
        $member = (new Member())->findForAssociation((int) $params['id'], Auth::associationId());
        if ($member === null) {
            Response::notFound();
        }
        $this->view('members.form', [
            'title'  => 'Edit Member',
            'member' => $member,
            'memberTypes' => $this->memberTypes(),
        ]);
        Session::clearFormState();
    }

    public function update(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $member = (new Member())->findForAssociation((int) $params['id'], $assocId);
        if ($member === null) {
            Response::notFound();
        }

        $data = $this->validated($request);
        $photo = $this->handlePhoto($request);
        if ($photo !== null) {
            (new ImageUploader())->delete($member['photo_path'] ?? null);
            $data['photo_path'] = $photo;
        }

        (new Member())->update((int) $member['id'], $data);
        $this->flash('success', 'Member updated.');
        $this->redirect('/members/' . $member['id']);
    }

    public function destroy(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $member = (new Member())->findForAssociation((int) $params['id'], $assocId);
        if ($member === null) {
            Response::notFound();
        }
        // Soft-delete to preserve financial history integrity.
        (new Member())->update((int) $member['id'], ['is_active' => 0]);
        $this->flash('success', 'Member deactivated.');
        $this->redirect('/members');
    }

    public function ledger(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $member = (new Member())->findWithType((int) $params['id'], $assocId);
        if ($member === null) {
            Response::notFound();
        }
        $ledger = (new MemberLedger())->build((int) $member['id']);
        $this->view('members.ledger', [
            'title'  => 'Ledger — ' . $member['name'],
            'member' => $member,
            'ledger' => $ledger,
        ]);
    }

    /** @return list<array<string,mixed>> */
    private function memberTypes(): array
    {
        return (new Master('member-types'))->activeForAssociation(Auth::associationId());
    }

    /** @return array<string,mixed> */
    private function validated(Request $request): array
    {
        $input = [
            'name'   => (string) $request->input('name', ''),
            'member_type_id' => $request->input('member_type_id') ?: null,
            'age'    => $request->input('age'),
            'gender' => (string) $request->input('gender', ''),
            'address' => (string) $request->input('address', ''),
            'mobile' => (string) $request->input('mobile', ''),
            'whatsapp' => (string) $request->input('whatsapp', ''),
            'email'  => (string) $request->input('email', ''),
            'family_members_count' => $request->input('family_members_count'),
            'occupation' => (string) $request->input('occupation', ''),
            'joined_on' => (string) $request->input('joined_on', ''),
            'notes'  => (string) $request->input('notes', ''),
            'is_active' => $request->input('is_active') !== null ? 1 : 1,
        ];
        $validator = Validator::make($input, [
            'name'   => 'required|min:2|max:180',
            'age'    => 'integer|between:0,150',
            'gender' => 'in:male,female,other',
            'mobile' => 'phone',
            'whatsapp' => 'phone',
            'email'  => 'email|max:190',
            'family_members_count' => 'integer|between:0,100',
            'joined_on' => 'date',
        ], [
            'family_members_count' => 'Number of family members',
        ]);
        if ($validator->fails()) {
            $this->withErrors($validator->errors(), $input);
        }

        // Normalise optional/empty values.
        $input['age'] = $input['age'] !== '' && $input['age'] !== null ? (int) $input['age'] : null;
        $input['family_members_count'] = $input['family_members_count'] !== '' && $input['family_members_count'] !== null ? (int) $input['family_members_count'] : null;
        $input['gender'] = $input['gender'] ?: null;
        $input['email'] = $input['email'] ?: null;
        $input['joined_on'] = $input['joined_on'] ?: null;
        foreach (['address', 'mobile', 'whatsapp', 'occupation', 'notes'] as $k) {
            $input[$k] = $input[$k] ?: null;
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
            return (new ImageUploader())->store($file, 'members');
        } catch (\RuntimeException $e) {
            $this->withErrors(['photo' => $e->getMessage()]);
        }
    }
}
