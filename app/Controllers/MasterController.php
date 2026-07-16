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

/**
 * Generic association-scoped CRUD for the simple master tables
 * (member types, income heads, expenditure heads, project types).
 * Bank accounts have their own richer controller.
 */
final class MasterController extends Controller
{
    public function index(Request $request, array $params): void
    {
        $key = $this->key($params);
        $assocId = Auth::associationId();
        $items = (new Master($key))->allForAssociation($assocId, 'name ASC');

        $this->view('masters.index', [
            'title'  => Master::LABELS[$key] . 's',
            'key'    => $key,
            'label'  => Master::LABELS[$key],
            'items'  => $items,
            'tabs'   => Master::LABELS,
        ]);
        Session::clearFormState();
    }

    public function create(Request $request, array $params): void
    {
        $key = $this->key($params);
        $this->view('masters.form', [
            'title' => 'Add ' . Master::LABELS[$key],
            'key'   => $key,
            'label' => Master::LABELS[$key],
            'item'  => null,
        ]);
        Session::clearFormState();
    }

    public function store(Request $request, array $params): void
    {
        $key = $this->key($params);
        $data = $this->validated($request, $key);
        $data['association_id'] = Auth::associationId();

        (new Master($key))->create($data);
        $this->flash('success', Master::LABELS[$key] . ' created.');
        $this->redirect('/masters/' . $key);
    }

    public function edit(Request $request, array $params): void
    {
        $key = $this->key($params);
        $item = (new Master($key))->findForAssociation((int) $params['id'], Auth::associationId());
        if ($item === null) {
            Response::notFound();
        }
        $this->view('masters.form', [
            'title' => 'Edit ' . Master::LABELS[$key],
            'key'   => $key,
            'label' => Master::LABELS[$key],
            'item'  => $item,
        ]);
        Session::clearFormState();
    }

    public function update(Request $request, array $params): void
    {
        $key = $this->key($params);
        $assocId = Auth::associationId();
        $model = new Master($key);
        $item = $model->findForAssociation((int) $params['id'], $assocId);
        if ($item === null) {
            Response::notFound();
        }

        $data = $this->validated($request, $key);
        $model->update((int) $item['id'], $data);
        $this->flash('success', Master::LABELS[$key] . ' updated.');
        $this->redirect('/masters/' . $key);
    }

    public function toggle(Request $request, array $params): void
    {
        $key = $this->key($params);
        $assocId = Auth::associationId();
        $model = new Master($key);
        $item = $model->findForAssociation((int) $params['id'], $assocId);
        if ($item === null) {
            Response::notFound();
        }
        $model->toggleActive((int) $item['id'], $assocId);
        $this->flash('success', Master::LABELS[$key] . ' status updated.');
        $this->redirect('/masters/' . $key);
    }

    private function key(array $params): string
    {
        $key = (string) ($params['master'] ?? '');
        if (!Master::isValidKey($key)) {
            Response::notFound('Unknown master list.');
        }
        return $key;
    }

    /** @return array<string,mixed> */
    private function validated(Request $request, string $key): array
    {
        $input = [
            'name'        => (string) $request->input('name', ''),
            'description' => (string) $request->input('description', ''),
            'is_active'   => $request->input('is_active') ? 1 : 0,
        ];
        $validator = Validator::make($input, [
            'name'        => 'required|min:1|max:120',
            'description' => 'max:255',
        ]);
        if ($validator->fails()) {
            $this->withErrors($validator->errors(), $input);
        }
        $input['description'] = $input['description'] ?: null;
        return $input;
    }
}
