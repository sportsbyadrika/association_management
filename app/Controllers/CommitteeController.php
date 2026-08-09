<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Association;
use App\Models\Committee;
use App\Models\CommitteeOfficial;
use App\Models\Master;
use App\Services\ImageUploader;
use App\Services\PdfReport;

/**
 * Committees (with a period + active status) and their officials.
 */
final class CommitteeController extends Controller
{
    public function index(Request $request): void
    {
        $assocId = Auth::associationId();
        $this->view('committees.index', [
            'title'      => 'Committees',
            'committees' => (new Committee())->allWithCounts($assocId),
        ]);
        Session::clearFormState();
    }

    public function create(Request $request): void
    {
        $this->view('committees.form', [
            'title'     => 'Add Committee',
            'committee' => null,
        ]);
        Session::clearFormState();
    }

    public function store(Request $request): void
    {
        $assocId = Auth::associationId();
        $data = $this->validated($request);
        $data['association_id'] = $assocId;
        $id = (new Committee())->create($data);
        $this->flash('success', 'Committee created.');
        $this->redirect('/committees/' . $id);
    }

    public function show(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $committee = (new Committee())->findForAssociation((int) $params['id'], $assocId);
        if ($committee === null) {
            Response::notFound();
        }
        $this->view('committees.show', [
            'title'     => $committee['name'],
            'committee' => $committee,
            'officials' => (new CommitteeOfficial())->forCommittee((int) $committee['id']),
        ]);
        Session::clearFormState();
    }

    public function edit(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $committee = (new Committee())->findForAssociation((int) $params['id'], $assocId);
        if ($committee === null) {
            Response::notFound();
        }
        $this->view('committees.form', [
            'title'     => 'Edit Committee',
            'committee' => $committee,
        ]);
        Session::clearFormState();
    }

    public function update(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $committee = (new Committee())->findForAssociation((int) $params['id'], $assocId);
        if ($committee === null) {
            Response::notFound();
        }
        $data = $this->validated($request);
        (new Committee())->update((int) $committee['id'], $data);
        $this->flash('success', 'Committee updated.');
        $this->redirect('/committees/' . $committee['id']);
    }

    public function destroy(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $committee = (new Committee())->findForAssociation((int) $params['id'], $assocId);
        if ($committee === null) {
            Response::notFound();
        }
        (new Committee())->delete((int) $committee['id']);
        $this->flash('success', 'Committee deleted.');
        $this->redirect('/committees');
    }

    /** PDF list of a committee's officials. */
    public function officialsPdf(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $committee = (new Committee())->findForAssociation((int) $params['id'], $assocId);
        if ($committee === null) {
            Response::notFound();
        }
        $officials = (new CommitteeOfficial())->forCommittee((int) $committee['id']);

        $period = '';
        if ($committee['start_date']) {
            $period = format_date($committee['start_date']) . ($committee['end_date'] ? ' – ' . format_date($committee['end_date']) : '');
        }
        $meta = array_filter([
            'Committee' => $committee['name'],
            'Period'    => $period,
            'Status'    => (int) $committee['is_active'] === 1 ? 'Active' : 'Inactive',
        ]);

        $this->pdf($assocId)->streamHtml(
            'committee-officials-' . $committee['id'],
            $committee['name'] . ' — Officials',
            $this->officialsHtml($officials),
            $meta,
            'landscape'
        );
    }

    /** @param list<array<string,mixed>> $officials */
    private function officialsHtml(array $officials): string
    {
        $esc = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
        $head = '<table class="data"><thead><tr>'
            . '<th>Sl No.</th><th>Photo</th><th>Designation</th><th>Name</th>'
            . '<th>Phone</th><th>Email</th><th>Address</th>'
            . '</tr></thead><tbody>';
        $body = '';
        $sl = 0;
        foreach ($officials as $o) {
            $uri = $this->photoDataUri($o['photo_path'] ?? null);
            $photo = $uri !== null ? '<img class="photo" src="' . $uri . '">' : '—';
            $body .= '<tr>'
                . '<td>' . (++$sl) . '</td>'
                . '<td>' . $photo . '</td>'
                . '<td>' . $esc($o['designation'] ?? '—') . '</td>'
                . '<td>' . $esc($o['name']) . '</td>'
                . '<td>' . $esc($o['phone'] ?? '') . '</td>'
                . '<td>' . $esc($o['email'] ?? '') . '</td>'
                . '<td>' . $esc($o['address'] ?? '') . '</td>'
                . '</tr>';
        }
        if ($officials === []) {
            $body = '<tr><td colspan="7" style="text-align:center;color:#9ca3af">No officials in this committee.</td></tr>';
        }
        return $head . $body . '</tbody></table>';
    }

    private function photoDataUri(?string $relativePath): ?string
    {
        if (!$relativePath) {
            return null;
        }
        $base = (new ImageUploader())->baseDir();
        $path = $base . '/' . ltrim($relativePath, '/');
        $real = realpath($path);
        if ($real === false || !str_starts_with($real, realpath($base) ?: $base) || !is_file($real)) {
            return null;
        }
        $data = @file_get_contents($real);
        if ($data === false) {
            return null;
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($data) ?: 'image/jpeg';
        return 'data:' . $mime . ';base64,' . base64_encode($data);
    }

    private function pdf(int $assocId): PdfReport
    {
        $association = (new Association())->find($assocId);
        $name = $association['name'] ?? 'Habitract';
        $logo = null;
        if (!empty($association['logo_path'])) {
            $candidate = (new ImageUploader())->baseDir() . '/' . $association['logo_path'];
            if (is_file($candidate)) {
                $logo = $candidate;
            }
        }
        return new PdfReport($name, $logo);
    }

    /** @return array<string,mixed> */
    private function validated(Request $request): array
    {
        $input = [
            'name'        => (string) $request->input('name', ''),
            'start_date'  => (string) $request->input('start_date', ''),
            'end_date'    => (string) $request->input('end_date', ''),
            'is_active'   => $request->input('is_active') ? 1 : 0,
            'description' => (string) $request->input('description', ''),
        ];
        $validator = Validator::make($input, [
            'name'        => 'required|min:2|max:180',
            'start_date'  => 'date',
            'end_date'    => 'date',
            'description' => 'max:1000',
        ]);
        if ($validator->fails()) {
            $this->withErrors($validator->errors(), $input);
        }
        $input['start_date'] = $input['start_date'] ?: null;
        $input['end_date'] = $input['end_date'] ?: null;
        $input['description'] = $input['description'] ?: null;
        return $input;
    }
}
