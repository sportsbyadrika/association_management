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

        if ((new Member())->memberNumberExists($assocId, (string) $data['member_number'])) {
            $this->withErrors(['member_number' => 'That member number is already in use.'], $data);
        }

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

        if ((new Member())->memberNumberExists($assocId, (string) $data['member_number'], (int) $member['id'])) {
            $this->withErrors(['member_number' => 'That member number is already in use.'], $data);
        }

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

    // ---- Bulk upload ----------------------------------------------------

    public function bulkForm(Request $request): void
    {
        $this->requireRole('association_admin');
        $this->view('members.bulk', ['title' => 'Bulk Upload Members']);
        Session::clearFormState();
    }

    public function bulkUpload(Request $request): void
    {
        $this->requireRole('association_admin');
        $assocId = Auth::associationId();
        $file = $request->file('csv');
        if ($file === null) {
            $this->withErrors(['csv' => 'Please choose a CSV file to upload.']);
        }
        $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($ext, ['csv', 'txt'], true)) {
            $this->withErrors(['csv' => 'The file must be a .csv file.']);
        }

        $handle = @fopen($file['tmp_name'], 'rb');
        if ($handle === false) {
            $this->withErrors(['csv' => 'The file could not be read.']);
        }

        // Read header row and map known columns.
        $header = fgetcsv($handle, 0, ',', '"', '');
        if ($header === false || $header === null) {
            fclose($handle);
            $this->withErrors(['csv' => 'The file appears to be empty.']);
        }
        $map = $this->headerMap($header);
        if (!isset($map['member_number']) || !isset($map['name'])) {
            fclose($handle);
            $this->withErrors(['csv' => 'The header row must include at least "member_number" and "name" columns.']);
        }

        $types = [];
        foreach ((new Master('member-types'))->activeForAssociation($assocId) as $t) {
            $types[mb_strtolower(trim($t['name']))] = (int) $t['id'];
        }

        $memberModel = new Member();
        $seen = [];         // member numbers seen in this file
        $added = 0;
        $errors = [];       // row => message
        $row = 1;

        $memberModel->db()->beginTransaction();
        try {
            while (($cols = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                $row++;
                if ($this->isBlankRow($cols)) {
                    continue;
                }
                $get = static fn (string $key) => isset($map[$key]) ? trim((string) ($cols[$map[$key]] ?? '')) : '';

                $number = $get('member_number');
                $name = $get('name');

                if ($number === '' || $name === '') {
                    $errors[] = "Row {$row}: member number and name are required.";
                    continue;
                }
                if (mb_strlen($number) > 50) {
                    $errors[] = "Row {$row}: member number is too long (max 50).";
                    continue;
                }
                $key = mb_strtolower($number);
                if (isset($seen[$key])) {
                    $errors[] = "Row {$row}: duplicate member number '{$number}' within the file.";
                    continue;
                }
                if ($memberModel->memberNumberExists($assocId, $number)) {
                    $errors[] = "Row {$row}: member number '{$number}' already exists.";
                    continue;
                }
                $seen[$key] = true;

                $gender = mb_strtolower($get('gender'));
                $gender = in_array($gender, ['male', 'female', 'other'], true) ? $gender : null;
                $typeId = null;
                $typeName = mb_strtolower($get('member_type'));
                if ($typeName !== '' && isset($types[$typeName])) {
                    $typeId = $types[$typeName];
                }
                $age = $get('age');
                $fam = $get('family_members_count');
                $joined = $get('joined_on');

                $memberModel->create([
                    'association_id' => $assocId,
                    'member_number'  => $number,
                    'member_type_id' => $typeId,
                    'name'           => mb_substr($name, 0, 180),
                    'age'            => ($age !== '' && is_numeric($age)) ? (int) $age : null,
                    'gender'         => $gender,
                    'mobile'         => $get('mobile') ?: null,
                    'whatsapp'       => $get('whatsapp') ?: null,
                    'email'          => (filter_var($get('email'), FILTER_VALIDATE_EMAIL)) ? $get('email') : null,
                    'family_members_count' => ($fam !== '' && is_numeric($fam)) ? (int) $fam : null,
                    'occupation'     => $get('occupation') ?: null,
                    'joined_on'      => ($joined !== '' && strtotime($joined)) ? date('Y-m-d', (int) strtotime($joined)) : null,
                    'is_active'      => 1,
                ]);
                $added++;
            }
            $memberModel->db()->commit();
        } catch (\Throwable $e) {
            $memberModel->db()->rollBack();
            fclose($handle);
            \App\Core\Logger::error('Bulk member upload failed: ' . $e->getMessage());
            $this->withErrors(['csv' => 'Upload failed while saving. No members were imported.']);
        }
        fclose($handle);

        $msg = "{$added} member(s) imported.";
        if ($errors !== []) {
            $shown = array_slice($errors, 0, 12);
            $msg .= ' ' . count($errors) . ' row(s) skipped: ' . implode(' ', $shown);
            if (count($errors) > 12) {
                $msg .= ' …';
            }
            $this->flash($added > 0 ? 'warning' : 'error', $msg);
        } else {
            $this->flash('success', $msg);
        }
        $this->redirect('/members');
    }

    /** Map a header row to known column indexes. @return array<string,int> */
    private function headerMap(array $header): array
    {
        $aliases = [
            'member_number' => ['member_number', 'member number', 'member no', 'member_no', 'memberno', 'number', 'id'],
            'name'          => ['name', 'full name', 'full_name', 'member name'],
            'member_type'   => ['member_type', 'member type', 'type'],
            'age'           => ['age'],
            'gender'        => ['gender'],
            'mobile'        => ['mobile', 'phone', 'mobile number'],
            'whatsapp'      => ['whatsapp', 'whatsapp number'],
            'email'         => ['email', 'e-mail'],
            'family_members_count' => ['family_members_count', 'family members', 'family', 'family_members'],
            'occupation'    => ['occupation'],
            'joined_on'     => ['joined_on', 'joined on', 'joining date', 'joined'],
        ];
        $map = [];
        foreach ($header as $i => $col) {
            $norm = mb_strtolower(trim((string) $col));
            foreach ($aliases as $field => $names) {
                if (in_array($norm, $names, true)) {
                    $map[$field] ??= $i;
                }
            }
        }
        return $map;
    }

    private function isBlankRow(array $cols): bool
    {
        foreach ($cols as $c) {
            if (trim((string) $c) !== '') {
                return false;
            }
        }
        return true;
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
            'member_number' => (string) $request->input('member_number', ''),
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
            'member_number' => 'required|max:50',
            'name'   => 'required|min:2|max:180',
            'age'    => 'integer|between:0,150',
            'gender' => 'in:male,female,other',
            'mobile' => 'phone',
            'whatsapp' => 'phone',
            'email'  => 'email|max:190',
            'family_members_count' => 'integer|between:0,100',
            'joined_on' => 'date',
        ], [
            'member_number' => 'Member number',
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
