<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\FinancialYear;

final class FinancialYearController extends Controller
{
    public function index(Request $request): void
    {
        $assocId = Auth::associationId();
        $this->view('financial_years.index', [
            'title' => 'Financial Years',
            'items' => (new FinancialYear())->allForAssociationOrdered($assocId),
            'current' => (new FinancialYear())->current($assocId),
        ]);
        Session::clearFormState();
    }

    public function create(Request $request): void
    {
        $this->view('financial_years.form', ['title' => 'Add Financial Year', 'item' => null]);
        Session::clearFormState();
    }

    public function store(Request $request): void
    {
        $data = $this->validated($request);
        $data['association_id'] = Auth::associationId();
        (new FinancialYear())->create($data);
        $this->flash('success', 'Financial year created.');
        $this->redirect('/masters/financial-years');
    }

    public function edit(Request $request, array $params): void
    {
        $item = (new FinancialYear())->findForAssociation((int) $params['id'], Auth::associationId());
        if ($item === null) {
            Response::notFound();
        }
        $this->view('financial_years.form', ['title' => 'Edit Financial Year', 'item' => $item]);
        Session::clearFormState();
    }

    public function update(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $model = new FinancialYear();
        $item = $model->findForAssociation((int) $params['id'], $assocId);
        if ($item === null) {
            Response::notFound();
        }
        $model->update((int) $item['id'], $this->validated($request));
        $this->flash('success', 'Financial year updated.');
        $this->redirect('/masters/financial-years');
    }

    public function toggle(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $model = new FinancialYear();
        $item = $model->findForAssociation((int) $params['id'], $assocId);
        if ($item === null) {
            Response::notFound();
        }
        $model->update((int) $item['id'], ['is_active' => (int) $item['is_active'] === 1 ? 0 : 1]);
        $this->flash('success', 'Financial year status updated.');
        $this->redirect('/masters/financial-years');
    }

    /** @return array<string,mixed> */
    private function validated(Request $request): array
    {
        $input = [
            'label'      => (string) $request->input('label', ''),
            'start_date' => (string) $request->input('start_date', ''),
            'end_date'   => (string) $request->input('end_date', ''),
            'is_active'  => $request->input('is_active') ? 1 : 0,
        ];
        $validator = Validator::make($input, [
            'label'      => 'required|min:2|max:60',
            'start_date' => 'required|date',
            'end_date'   => 'required|date',
        ], ['start_date' => 'From date', 'end_date' => 'To date']);
        if ($validator->fails()) {
            $this->withErrors($validator->errors(), $input);
        }
        if (strtotime($input['end_date']) < strtotime($input['start_date'])) {
            $this->withErrors(['end_date' => 'To date must be on or after the from date.'], $input);
        }
        return $input;
    }
}
