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

    /**
     * Wizard step 2 (parse): store the uploaded CSV and go to the review page.
     */
    public function bulkParse(Request $request): void
    {
        $this->requireRole('association_admin');

        $file = $request->file('csv');
        if ($file === null) {
            $this->withErrors(['csv' => 'Please choose a CSV file to upload.']);
        }
        $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($ext, ['csv', 'txt'], true)) {
            $this->withErrors(['csv' => 'The file must be a .csv file.']);
        }
        if ((int) ($file['size'] ?? 0) > 2 * 1024 * 1024) {
            $this->withErrors(['csv' => 'The file must be 2 MB or smaller.']);
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            $this->withErrors(['csv' => 'Invalid upload.']);
        }

        $token = bin2hex(random_bytes(16));
        $dest = $this->bulkCachePath($token);
        if (!@move_uploaded_file($file['tmp_name'], $dest) && !@copy($file['tmp_name'], $dest)) {
            $this->withErrors(['csv' => 'Could not store the uploaded file. Please check storage permissions and try again.']);
        }

        Session::set('__bulk_upload', ['token' => $token, 'name' => (string) ($file['name'] ?? 'upload.csv')]);
        $this->redirect('/members/bulk/preview');
    }

    /**
     * Wizard step 3 (review): validate every row and show a preview table.
     */
    public function bulkPreview(Request $request): void
    {
        $this->requireRole('association_admin');

        $path = $this->resolveBulkFile(Session::get('__bulk_upload'));
        if ($path === null) {
            Session::flash('error', 'Your upload has expired. Please upload the file again.');
            $this->redirect('/members/bulk');
        }

        $parsed = $this->parseBulkFile($path, Auth::associationId());
        if ($parsed['headerError'] !== null) {
            @unlink($path);
            Session::forget('__bulk_upload');
            Session::flash('error', $parsed['headerError']);
            $this->redirect('/members/bulk');
        }

        $meta = Session::get('__bulk_upload');
        $this->view('members.bulk_preview', [
            'title'    => 'Bulk Upload — Review',
            'parsed'   => $parsed,
            'fileName' => (string) ($meta['name'] ?? 'upload.csv'),
        ]);
    }

    /**
     * Wizard step 4 (import): insert the valid rows, resilient to per-row errors.
     */
    public function bulkImport(Request $request): void
    {
        $this->requireRole('association_admin');
        $assocId = Auth::associationId();

        $path = $this->resolveBulkFile(Session::get('__bulk_upload'));
        if ($path === null) {
            Session::flash('error', 'Your upload has expired. Please upload the file again.');
            $this->redirect('/members/bulk');
        }

        $parsed = $this->parseBulkFile($path, $assocId);
        $model = new Member();
        $imported = 0;
        $failed = [];

        foreach ($parsed['rows'] as $r) {
            if (!$r['valid']) {
                continue;
            }
            try {
                $model->create($r['data']);
                $imported++;
            } catch (\Throwable $e) {
                \App\Core\Logger::error('Bulk import row failed', ['line' => $r['line'], 'error' => $e->getMessage()]);
                $failed[] = 'Row ' . $r['line'] . ': ' . $this->shortDbError($e->getMessage());
            }
        }

        @unlink($path);
        Session::forget('__bulk_upload');

        $msg = "{$imported} member(s) imported.";
        if ($parsed['invalidCount'] > 0) {
            $msg .= " {$parsed['invalidCount']} row(s) with validation issues were skipped.";
        }
        if ($failed !== []) {
            $msg .= ' ' . count($failed) . ' failed to save: ' . implode(' ', array_slice($failed, 0, 6));
            $this->flash($imported > 0 ? 'warning' : 'error', $msg);
        } else {
            $this->flash($imported > 0 ? 'success' : 'warning', $msg);
        }
        $this->redirect('/members');
    }

    /**
     * Parse + validate a bulk CSV file (no writes). Each row carries a
     * validity flag, an error reason and the normalised insert data.
     *
     * @return array{headerError:?string,rows:list<array<string,mixed>>,validCount:int,invalidCount:int}
     */
    private function parseBulkFile(string $path, int $assocId): array
    {
        $result = ['headerError' => null, 'rows' => [], 'validCount' => 0, 'invalidCount' => 0];

        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            $result['headerError'] = 'The file could not be read.';
            return $result;
        }
        // Skip a UTF-8 BOM if present.
        if (fread($handle, 3) !== "\xEF\xBB\xBF") {
            rewind($handle);
        }
        $header = fgetcsv($handle, 0, ',', '"', '');
        if ($header === false || $header === null) {
            fclose($handle);
            $result['headerError'] = 'The file appears to be empty.';
            return $result;
        }
        $map = $this->headerMap($header);
        if (!isset($map['member_number']) || !isset($map['name'])) {
            fclose($handle);
            $result['headerError'] = 'The header row must include at least "member_number" and "name" columns.';
            return $result;
        }

        $types = [];
        foreach ((new Master('member-types'))->activeForAssociation($assocId) as $t) {
            $types[mb_strtolower(trim($t['name']))] = (int) $t['id'];
        }

        $model = new Member();
        $seen = [];
        $line = 1;

        while (($cols = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            $line++;
            if ($this->isBlankRow($cols)) {
                continue;
            }
            $get = static fn (string $key) => isset($map[$key]) ? trim((string) ($cols[$map[$key]] ?? '')) : '';

            $number = $get('member_number');
            $name = $get('name');

            $error = null;
            if ($number === '' || $name === '') {
                $error = 'Member number and name are required.';
            } elseif (mb_strlen($number) > 50) {
                $error = 'Member number is too long (max 50).';
            } elseif (isset($seen[mb_strtolower($number)])) {
                $error = "Duplicate member number '{$number}' within the file.";
            } elseif ($model->memberNumberExists($assocId, $number)) {
                $error = "Member number '{$number}' already exists.";
            }

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

            $data = [
                'association_id' => $assocId,
                'member_number'  => $number,
                'member_type_id' => $typeId,
                'name'           => mb_substr($name, 0, 180),
                'age'            => ($age !== '' && is_numeric($age)) ? (int) $age : null,
                'gender'         => $gender,
                'mobile'         => $get('mobile') ?: null,
                'whatsapp'       => $get('whatsapp') ?: null,
                'email'          => filter_var($get('email'), FILTER_VALIDATE_EMAIL) ? $get('email') : null,
                'family_members_count' => ($fam !== '' && is_numeric($fam)) ? (int) $fam : null,
                'occupation'     => $get('occupation') ?: null,
                'joined_on'      => ($joined !== '' && strtotime($joined)) ? date('Y-m-d', (int) strtotime($joined)) : null,
                'is_active'      => 1,
            ];

            $valid = $error === null;
            if ($valid) {
                $seen[mb_strtolower($number)] = true;
                $result['validCount']++;
            } else {
                $result['invalidCount']++;
            }

            $result['rows'][] = [
                'line'    => $line,
                'valid'   => $valid,
                'error'   => $error,
                'data'    => $data,
                'display' => [
                    'member_number' => $number,
                    'name'          => $name,
                    'member_type'   => $get('member_type'),
                    'mobile'        => $get('mobile'),
                    'email'         => $get('email'),
                ],
            ];
        }
        fclose($handle);

        return $result;
    }

    /** Resolve the cached upload file path from the session token. */
    private function resolveBulkFile(mixed $meta): ?string
    {
        if (!is_array($meta) || empty($meta['token']) || !preg_match('/^[a-f0-9]{32}$/', (string) $meta['token'])) {
            return null;
        }
        $path = $this->bulkCachePath((string) $meta['token']);
        return is_file($path) ? $path : null;
    }

    private function bulkCachePath(string $token): string
    {
        $dir = dirname(__DIR__, 2) . '/storage/cache';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $dir . '/bulk_' . $token . '.csv';
    }

    /** Turn a raw DB error into a short, admin-friendly hint. */
    private function shortDbError(string $msg): string
    {
        if (stripos($msg, 'member_number') !== false && stripos($msg, 'column') !== false) {
            return 'database not up to date — run migration 004 (member_number column missing).';
        }
        if (stripos($msg, 'Duplicate entry') !== false) {
            return 'duplicate member number.';
        }
        return 'could not be saved (database error).';
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
