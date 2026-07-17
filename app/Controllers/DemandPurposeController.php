<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\DemandPurpose;

final class DemandPurposeController extends Controller
{
    public function index(Request $request): void
    {
        $assocId = Auth::associationId();
        $this->view('demand_purposes.index', [
            'title' => 'Demand Purposes',
            'items' => (new DemandPurpose())->allForAssociationOrdered($assocId),
        ]);
        Session::clearFormState();
    }

    public function create(Request $request): void
    {
        $this->view('demand_purposes.form', ['title' => 'Add Demand Purpose', 'item' => null]);
        Session::clearFormState();
    }

    public function store(Request $request): void
    {
        $data = $this->validated($request);
        $data['association_id'] = Auth::associationId();
        (new DemandPurpose())->create($data);
        $this->flash('success', 'Demand purpose created.');
        $this->redirect('/masters/demand-purposes');
    }

    public function edit(Request $request, array $params): void
    {
        $item = (new DemandPurpose())->findForAssociation((int) $params['id'], Auth::associationId());
        if ($item === null) {
            Response::notFound();
        }
        $this->view('demand_purposes.form', ['title' => 'Edit Demand Purpose', 'item' => $item]);
        Session::clearFormState();
    }

    public function update(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $model = new DemandPurpose();
        $item = $model->findForAssociation((int) $params['id'], $assocId);
        if ($item === null) {
            Response::notFound();
        }
        $model->update((int) $item['id'], $this->validated($request));
        $this->flash('success', 'Demand purpose updated.');
        $this->redirect('/masters/demand-purposes');
    }

    public function toggle(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $model = new DemandPurpose();
        $item = $model->findForAssociation((int) $params['id'], $assocId);
        if ($item === null) {
            Response::notFound();
        }
        $model->toggleActive((int) $item['id'], $assocId);
        $this->flash('success', 'Demand purpose status updated.');
        $this->redirect('/masters/demand-purposes');
    }

    /** @return array<string,mixed> */
    private function validated(Request $request): array
    {
        $input = [
            'name'      => (string) $request->input('name', ''),
            'type'      => (string) $request->input('type', 'optional'),
            'is_active' => $request->input('is_active') ? 1 : 0,
        ];
        $validator = Validator::make($input, [
            'name' => 'required|min:1|max:120',
            'type' => 'required|in:mandatory,optional',
        ]);
        if ($validator->fails()) {
            $this->withErrors($validator->errors(), $input);
        }
        return $input;
    }
}
