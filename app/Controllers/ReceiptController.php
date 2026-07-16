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
use App\Models\Master;
use App\Models\Member;
use App\Models\Project;
use App\Models\Receipt;

final class ReceiptController extends Controller
{
    public function index(Request $request): void
    {
        $assocId = Auth::associationId();
        $page = (int) $request->input('page', 1);
        $result = (new Receipt())->paginateForAssociation($assocId, $page, 20);

        $this->view('receipts.index', [
            'title'     => 'Receipts',
            'receipts'  => $result['data'],
            'paginator' => $result,
        ]);
        Session::clearFormState();
    }

    public function create(Request $request): void
    {
        $assocId = Auth::associationId();
        $this->view('receipts.form', [
            'title'       => 'Record Receipt',
            'members'     => (new Member())->options($assocId),
            'incomeHeads' => (new Master('income-heads'))->activeForAssociation($assocId),
            'projects'    => (new Project())->options($assocId),
            'bankAccounts' => (new BankAccount())->options($assocId),
            'selectedMember'  => (int) $request->input('member_id', 0),
            'selectedProject' => (int) $request->input('project_id', 0),
        ]);
        Session::clearFormState();
    }

    public function store(Request $request): void
    {
        $assocId = Auth::associationId();
        $input = [
            'member_id'      => $request->input('member_id') ?: null,
            'income_head_id' => $request->input('income_head_id') ?: null,
            'project_id'     => $request->input('project_id') ?: null,
            'amount'         => (string) $request->input('amount', ''),
            'mode'           => (string) $request->input('mode', 'cash'),
            'bank_account_id' => $request->input('bank_account_id') ?: null,
            'received_on'    => (string) $request->input('received_on', ''),
            'remarks'        => (string) $request->input('remarks', ''),
        ];
        $validator = Validator::make($input, [
            'amount'      => 'required|decimal|min_val:0.01',
            'mode'        => 'required|in:cash,fund_transfer',
            'received_on' => 'required|date',
            'remarks'     => 'max:500',
        ]);
        if ($validator->fails()) {
            $this->withErrors($validator->errors(), $input);
        }

        // Fund transfers should reference a bank account.
        if ($input['mode'] === 'fund_transfer' && $input['bank_account_id'] === null) {
            $this->withErrors(['bank_account_id' => 'Select the bank account for a fund transfer.'], $input);
        }

        $this->assertTenant($assocId, $input);

        (new Receipt())->create([
            'association_id'  => $assocId,
            'member_id'       => $input['member_id'],
            'income_head_id'  => $input['income_head_id'],
            'project_id'      => $input['project_id'],
            'amount'          => $input['amount'],
            'mode'            => $input['mode'],
            'bank_account_id' => $input['mode'] === 'fund_transfer' ? $input['bank_account_id'] : ($input['bank_account_id'] ?: null),
            'received_on'     => $input['received_on'],
            'remarks'         => $input['remarks'] ?: null,
            'created_by'      => Auth::id(),
        ]);

        $this->flash('success', 'Receipt recorded.');
        $this->redirect('/receipts');
    }

    public function destroy(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $receipt = (new Receipt())->findForAssociation((int) $params['id'], $assocId);
        if ($receipt === null) {
            Response::notFound();
        }
        (new Receipt())->delete((int) $receipt['id']);
        $this->flash('success', 'Receipt deleted.');
        $this->redirect('/receipts');
    }

    /** Verify all referenced foreign records belong to this association. */
    private function assertTenant(int $assocId, array $input): void
    {
        if ($input['member_id'] !== null && (new Member())->findForAssociation((int) $input['member_id'], $assocId) === null) {
            $this->withErrors(['member_id' => 'Invalid member.'], $input);
        }
        if ($input['income_head_id'] !== null && (new Master('income-heads'))->findForAssociation((int) $input['income_head_id'], $assocId) === null) {
            $this->withErrors(['income_head_id' => 'Invalid income head.'], $input);
        }
        if ($input['project_id'] !== null && (new Project())->findForAssociation((int) $input['project_id'], $assocId) === null) {
            $this->withErrors(['project_id' => 'Invalid project.'], $input);
        }
        if ($input['bank_account_id'] !== null && (new BankAccount())->findForAssociation((int) $input['bank_account_id'], $assocId) === null) {
            $this->withErrors(['bank_account_id' => 'Invalid bank account.'], $input);
        }
    }
}
