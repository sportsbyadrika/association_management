<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\BankAccount;
use App\Models\Expenditure;
use App\Models\Master;
use App\Models\Project;

final class ExpenditureController extends Controller
{
    public function index(Request $request): void
    {
        $assocId = Auth::associationId();
        $page = (int) $request->input('page', 1);
        $result = (new Expenditure())->paginateForAssociation($assocId, $page, 20);

        $this->view('expenditures.index', [
            'title'        => 'Expenditure',
            'expenditures' => $result['data'],
            'paginator'    => $result,
        ]);
        Session::clearFormState();
    }

    public function create(Request $request): void
    {
        $assocId = Auth::associationId();
        $this->view('expenditures.form', [
            'title'            => 'Record Expenditure',
            'heads'            => (new Master('expenditure-heads'))->activeForAssociation($assocId),
            'projects'         => (new Project())->options($assocId),
            'bankAccounts'     => (new BankAccount())->options($assocId),
            'selectedProject'  => (int) $request->input('project_id', 0),
        ]);
        Session::clearFormState();
    }

    public function store(Request $request): void
    {
        $assocId = Auth::associationId();
        $input = [
            'expenditure_head_id' => $request->input('expenditure_head_id') ?: null,
            'project_id' => $request->input('project_id') ?: null,
            'category'   => (string) $request->input('category', 'association'),
            'amount'     => (string) $request->input('amount', ''),
            'paid_on'    => (string) $request->input('paid_on', ''),
            'bank_account_id' => $request->input('bank_account_id') ?: null,
            'mode'       => (string) $request->input('mode', 'cash'),
            'remarks'    => (string) $request->input('remarks', ''),
        ];
        $validator = Validator::make($input, [
            'category' => 'required|in:project,association',
            'amount'   => 'required|decimal|min_val:0.01',
            'paid_on'  => 'required|date',
            'mode'     => 'required|in:cash,fund_transfer',
            'remarks'  => 'max:500',
        ]);
        if ($validator->fails()) {
            $this->withErrors($validator->errors(), $input);
        }

        if ($input['category'] === 'project' && $input['project_id'] === null) {
            $this->withErrors(['project_id' => 'Select the project this expense belongs to.'], $input);
        }
        if ($input['mode'] === 'fund_transfer' && $input['bank_account_id'] === null) {
            $this->withErrors(['bank_account_id' => 'Select the bank account for a fund transfer.'], $input);
        }
        $this->assertTenant($assocId, $input);

        (new Expenditure())->create([
            'association_id'      => $assocId,
            'expenditure_head_id' => $input['expenditure_head_id'],
            'project_id'          => $input['category'] === 'project' ? $input['project_id'] : null,
            'category'            => $input['category'],
            'amount'              => $input['amount'],
            'paid_on'             => $input['paid_on'],
            'bank_account_id'     => $input['bank_account_id'],
            'mode'                => $input['mode'],
            'remarks'             => $input['remarks'] ?: null,
            'created_by'          => Auth::id(),
        ]);

        $this->flash('success', 'Expenditure recorded.');
        $this->redirect('/expenditures');
    }

    public function destroy(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $exp = (new Expenditure())->findForAssociation((int) $params['id'], $assocId);
        if ($exp === null) {
            Response::notFound();
        }
        (new Expenditure())->delete((int) $exp['id']);
        $this->flash('success', 'Expenditure deleted.');
        $this->redirect('/expenditures');
    }

    private function assertTenant(int $assocId, array $input): void
    {
        if ($input['expenditure_head_id'] !== null && (new Master('expenditure-heads'))->findForAssociation((int) $input['expenditure_head_id'], $assocId) === null) {
            $this->withErrors(['expenditure_head_id' => 'Invalid expenditure head.'], $input);
        }
        if ($input['project_id'] !== null && (new Project())->findForAssociation((int) $input['project_id'], $assocId) === null) {
            $this->withErrors(['project_id' => 'Invalid project.'], $input);
        }
        if ($input['bank_account_id'] !== null && (new BankAccount())->findForAssociation((int) $input['bank_account_id'], $assocId) === null) {
            $this->withErrors(['bank_account_id' => 'Invalid bank account.'], $input);
        }
    }
}
