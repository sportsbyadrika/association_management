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
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Services\ImageUploader;

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
        $this->view('projects.show', [
            'title'      => $project['name'],
            'project'    => $project,
            'milestones' => (new ProjectMilestone())->forProject((int) $project['id']),
            'collected'  => $projectModel->collected((int) $project['id']),
            'spent'      => $projectModel->spent((int) $project['id']),
        ]);
        Session::clearFormState();
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
