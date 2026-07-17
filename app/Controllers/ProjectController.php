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
use App\Models\Master;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Services\ImageUploader;
use App\Services\PdfReport;

final class ProjectController extends Controller
{
    public function index(Request $request): void
    {
        $assocId = Auth::associationId();
        $this->view('projects.index', [
            'title'    => 'Projects',
            'projects' => (new Project())->allWithType($assocId),
        ]);
        Session::clearFormState();
    }

    public function create(Request $request): void
    {
        $this->view('projects.form', [
            'title'   => 'New Project',
            'project' => null,
            'types'   => (new Master('project-types'))->activeForAssociation(Auth::associationId()),
        ]);
        Session::clearFormState();
    }

    public function store(Request $request): void
    {
        $assocId = Auth::associationId();
        $data = $this->validated($request, $assocId);
        $data['association_id'] = $assocId;
        $id = (new Project())->create($data);
        $this->flash('success', 'Project created.');
        $this->redirect('/projects/' . $id);
    }

    public function show(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $project = (new Project())->findWithType((int) $params['id'], $assocId);
        if ($project === null) {
            Response::notFound();
        }
        $projectModel = new Project();
        $breakdown = $this->demandBreakdown((int) $project['id'], $assocId);
        $this->view('projects.show', [
            'title'      => $project['name'],
            'project'    => $project,
            'milestones' => (new ProjectMilestone())->forProject((int) $project['id']),
            'collected'  => $projectModel->collected((int) $project['id']),
            'spent'      => $projectModel->spent((int) $project['id']),
            'received'   => $breakdown['received'],
            'pending'    => $breakdown['pending'],
            'demandTotals' => [
                'demanded' => $breakdown['total_demanded'],
                'received' => $breakdown['total_received'],
            ],
        ]);
        Session::clearFormState();
    }

    /**
     * Printable (PDF) project ledger: project details + a demand list with
     * each member's payment status and received date.
     */
    public function ledger(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $project = (new Project())->findWithType((int) $params['id'], $assocId);
        if ($project === null) {
            Response::notFound();
        }
        $model = new Project();
        $collected = $model->collected((int) $project['id']);
        $spent = $model->spent((int) $project['id']);
        $breakdown = $this->demandBreakdown((int) $project['id'], $assocId);

        $columns = ['Member No.', 'Name', 'Demand Amount', 'Status', 'Received On'];
        $rows = [];
        foreach ($breakdown['all'] as $e) {
            $rows[] = [
                $e['member_number'] ?: '-',
                $e['name'],
                number_format($e['amount'], 2),
                $e['status'],
                $e['received_on'] ? format_date($e['received_on']) : '-',
            ];
        }

        $meta = [
            'Type'   => $project['project_type_name'] ?? 'General',
            'Status' => ucfirst(str_replace('_', ' ', (string) $project['status'])),
        ];
        $summary = [
            'Target'    => number_format((float) $project['target_amount'], 2),
            'Demanded'  => number_format($breakdown['total_demanded'], 2),
            'Received'  => number_format($breakdown['total_received'], 2),
            'Collected' => number_format($collected, 2),
            'Spent'     => number_format($spent, 2),
        ];

        $this->pdf($assocId)->stream(
            'project-ledger-' . $project['id'],
            'Project Ledger — ' . $project['name'],
            $columns,
            $rows,
            $meta,
            $summary
        );
    }

    /**
     * Split a project's demands into received / pending with computed status.
     * @return array{received:list,pending:list,all:list,total_demanded:float,total_received:float}
     */
    private function demandBreakdown(int $projectId, int $assocId): array
    {
        $rows = (new Project())->demandLedger($projectId, $assocId);
        $received = [];
        $pending = [];
        $all = [];
        $totalDemanded = 0.0;
        $totalReceived = 0.0;

        foreach ($rows as $r) {
            $amount = (float) $r['amount'];
            $paid = (float) $r['paid'];
            $fully = $r['status'] === 'paid' || $paid >= $amount;
            $entry = [
                'member_number' => $r['member_number'],
                'name'          => $r['name'],
                'amount'        => $amount,
                'paid'          => $paid,
                'status'        => $fully ? 'Received' : ($paid > 0 ? 'Partial' : 'Pending'),
                'received_on'   => $r['last_received'],
            ];
            $totalDemanded += $amount;
            $totalReceived += $paid;
            $all[] = $entry;
            if ($fully) {
                $received[] = $entry;
            } else {
                $pending[] = $entry;
            }
        }

        return [
            'received'       => $received,
            'pending'        => $pending,
            'all'            => $all,
            'total_demanded' => $totalDemanded,
            'total_received' => $totalReceived,
        ];
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

    public function edit(Request $request, array $params): void
    {
        $project = (new Project())->findForAssociation((int) $params['id'], Auth::associationId());
        if ($project === null) {
            Response::notFound();
        }
        $this->view('projects.form', [
            'title'   => 'Edit Project',
            'project' => $project,
            'types'   => (new Master('project-types'))->activeForAssociation(Auth::associationId()),
        ]);
        Session::clearFormState();
    }

    public function update(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $project = (new Project())->findForAssociation((int) $params['id'], $assocId);
        if ($project === null) {
            Response::notFound();
        }
        $data = $this->validated($request, $assocId);
        (new Project())->update((int) $project['id'], $data);
        $this->flash('success', 'Project updated.');
        $this->redirect('/projects/' . $project['id']);
    }

    public function storeMilestone(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $project = (new Project())->findForAssociation((int) $params['id'], $assocId);
        if ($project === null) {
            Response::notFound();
        }

        $input = [
            'title'       => (string) $request->input('title', ''),
            'description' => (string) $request->input('description', ''),
            'achieved_on' => (string) $request->input('achieved_on', ''),
        ];
        $validator = Validator::make($input, [
            'title'       => 'required|min:2|max:180',
            'description' => 'max:2000',
            'achieved_on' => 'date',
        ]);
        if ($validator->fails()) {
            $this->withErrors($validator->errors(), $input);
        }

        $photo = null;
        $file = $request->file('photo');
        if ($file !== null) {
            try {
                $photo = (new ImageUploader())->store($file, 'projects');
            } catch (\RuntimeException $e) {
                $this->withErrors(['photo' => $e->getMessage()], $input);
            }
        }

        (new ProjectMilestone())->create([
            'project_id'  => (int) $project['id'],
            'title'       => $input['title'],
            'description' => $input['description'] ?: null,
            'photo_path'  => $photo,
            'achieved_on' => $input['achieved_on'] ?: null,
        ]);

        $this->flash('success', 'Milestone added.');
        $this->redirect('/projects/' . $project['id']);
    }

    /** @return array<string,mixed> */
    private function validated(Request $request, int $assocId): array
    {
        $input = [
            'project_type_id' => $request->input('project_type_id') ?: null,
            'name'         => (string) $request->input('name', ''),
            'description'  => (string) $request->input('description', ''),
            'status'       => (string) $request->input('status', 'active'),
            'target_amount' => (string) $request->input('target_amount', '0'),
            'start_date'   => (string) $request->input('start_date', ''),
            'end_date'     => (string) $request->input('end_date', ''),
        ];
        $validator = Validator::make($input, [
            'name'          => 'required|min:2|max:180',
            'status'        => 'required|in:planned,active,completed,on_hold,cancelled',
            'target_amount' => 'decimal|min_val:0',
            'start_date'    => 'date',
            'end_date'      => 'date',
        ]);
        if ($validator->fails()) {
            $this->withErrors($validator->errors(), $input);
        }
        if ($input['project_type_id'] !== null && (new Master('project-types'))->findForAssociation((int) $input['project_type_id'], $assocId) === null) {
            $this->withErrors(['project_type_id' => 'Invalid project type.'], $input);
        }

        $input['target_amount'] = $input['target_amount'] !== '' ? $input['target_amount'] : '0';
        $input['description'] = $input['description'] ?: null;
        $input['start_date'] = $input['start_date'] ?: null;
        $input['end_date'] = $input['end_date'] ?: null;
        return $input;
    }
}
