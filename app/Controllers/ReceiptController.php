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
use App\Models\Demand;
use App\Models\DemandPurpose;
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
        $search = trim((string) $request->input('q', ''));
        $projectFilter = (string) $request->input('project_id', '');
        [$from, $to] = $this->filterDates($request);

        $result = (new Receipt())->paginateForAssociation($assocId, $page, 20, $search, $projectFilter, $from, $to);

        $this->view('receipts.index', [
            'title'         => 'Receipts',
            'receipts'      => $result['data'],
            'paginator'     => $result,
            'projects'      => (new Project())->options($assocId),
            'search'        => $search,
            'projectFilter' => $projectFilter,
            'from'          => $from,
            'to'            => $to,
        ]);
        Session::clearFormState();
    }

    public function create(Request $request): void
    {
        $assocId = Auth::associationId();

        $selectedMember = (int) $request->input('member_id', 0);
        $selectedProject = (int) $request->input('project_id', 0);
        $demandId = (int) $request->input('demand_id', 0);
        $demand = null;
        $prefillAmount = '';
        $returnLedger = 0;

        // If recording against a specific demand, prefill from it.
        if ($demandId > 0) {
            $demand = (new Demand())->findForAssociation($demandId, $assocId);
            if ($demand !== null) {
                $selectedMember = (int) $demand['member_id'];
                $selectedProject = $demand['project_id'] ? (int) $demand['project_id'] : 0;
                $paid = (new Receipt())->totalForDemand($demandId);
                $prefillAmount = number_format(max(0, (float) $demand['amount'] - $paid), 2, '.', '');
                $returnLedger = (int) $demand['member_id'];
            } else {
                $demandId = 0; // not ours — ignore
            }
        }

        // Members may also pass an explicit return-to-ledger member id.
        if ($returnLedger === 0) {
            $returnLedger = (int) $request->input('return_ledger', 0);
        }

        $incomeHeads = (new Master('income-heads'))->activeForAssociation($assocId);

        // Auto-select the income head that matches the demand: a project-linked
        // demand -> "Project Contribution"; otherwise match the purpose name.
        $selectedIncomeHead = (int) $request->input('income_head_id', 0);
        if ($demand !== null && $selectedIncomeHead === 0) {
            $wanted = [];
            if (!empty($demand['project_id'])) {
                $wanted = ['project contribution', 'project'];
            } else {
                $purpose = (new DemandPurpose())->find((int) ($demand['demand_purpose_id'] ?? 0));
                if ($purpose !== null) {
                    $wanted[] = mb_strtolower(trim((string) $purpose['name']));
                }
            }
            foreach ($incomeHeads as $h) {
                if ($wanted !== [] && in_array(mb_strtolower(trim((string) $h['name'])), $wanted, true)) {
                    $selectedIncomeHead = (int) $h['id'];
                    break;
                }
            }
        }

        $demandPurposeName = null;
        if ($demand !== null) {
            $dp = (new DemandPurpose())->find((int) ($demand['demand_purpose_id'] ?? 0));
            $demandPurposeName = $dp['name'] ?? null;
        }

        $this->view('receipts.form', [
            'title'              => 'Record Receipt',
            'members'            => (new Member())->options($assocId),
            'incomeHeads'        => $incomeHeads,
            'projects'           => (new Project())->options($assocId),
            'demandPurposeName'  => $demandPurposeName,
            'bankAccounts'       => (new BankAccount())->options($assocId),
            'selectedMember'     => $selectedMember,
            'selectedProject'    => $selectedProject,
            'selectedIncomeHead' => $selectedIncomeHead,
            'demand'             => $demand,
            'demandId'           => $demandId,
            'prefillAmount'      => $prefillAmount,
            'returnLedger'       => $returnLedger,
        ]);
        Session::clearFormState();
    }

    public function store(Request $request): void
    {
        $assocId = Auth::associationId();
        $input = $this->validatedInput($request);
        $returnLedger = (int) $request->input('return_ledger', 0);

        // A linked demand must belong to this association; align member/project.
        $demand = null;
        if ($input['demand_id'] !== null) {
            $demand = (new Demand())->findForAssociation((int) $input['demand_id'], $assocId);
            if ($demand === null) {
                $this->withErrors(['amount' => 'The linked demand is invalid.'], $input);
            }
            $input['member_id'] = (int) $demand['member_id'];
            if ($demand['project_id']) {
                $input['project_id'] = (int) $demand['project_id'];
            }
        }

        $receipt = new Receipt();
        $receipt->create([
            'association_id'  => $assocId,
            'member_id'       => $input['member_id'],
            'income_head_id'  => $input['income_head_id'],
            'project_id'      => $input['project_id'],
            'demand_id'       => $input['demand_id'],
            'amount'          => $input['amount'],
            'mode'            => $input['mode'],
            'bank_account_id' => $input['mode'] === 'fund_transfer' ? $input['bank_account_id'] : ($input['bank_account_id'] ?: null),
            'received_on'     => $input['received_on'],
            'remarks'         => $input['remarks'] ?: null,
            'created_by'      => Auth::id(),
        ]);

        // Keep the demand's status in sync (pending -> partial/paid).
        if ($demand !== null) {
            (new Demand())->syncStatus((int) $demand['id']);
        }

        $this->flash('success', 'Receipt recorded.');

        // Return to the member's ledger when the receipt was raised from there.
        if ($returnLedger > 0 && (new Member())->findForAssociation($returnLedger, $assocId) !== null) {
            $this->redirect('/members/' . $returnLedger . '/ledger');
        }
        $this->redirect('/receipts');
    }

    public function edit(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $receipt = (new Receipt())->findForAssociation((int) $params['id'], $assocId);
        if ($receipt === null) {
            Response::notFound();
        }

        $demand = $receipt['demand_id'] ? (new Demand())->findForAssociation((int) $receipt['demand_id'], $assocId) : null;
        $demandPurposeName = null;
        if ($demand !== null) {
            $dp = (new DemandPurpose())->find((int) ($demand['demand_purpose_id'] ?? 0));
            $demandPurposeName = $dp['name'] ?? null;
        }

        $this->view('receipts.form', [
            'title'              => 'Edit Receipt',
            'receipt'            => $receipt,
            'members'            => (new Member())->options($assocId),
            'incomeHeads'        => (new Master('income-heads'))->activeForAssociation($assocId),
            'projects'           => (new Project())->options($assocId),
            'demandPurposeName'  => $demandPurposeName,
            'bankAccounts'       => (new BankAccount())->options($assocId),
            'selectedMember'     => (int) ($receipt['member_id'] ?? 0),
            'selectedProject'    => (int) ($receipt['project_id'] ?? 0),
            'selectedIncomeHead' => (int) ($receipt['income_head_id'] ?? 0),
            'demand'             => $demand,
            'demandId'           => (int) ($receipt['demand_id'] ?? 0),
            'prefillAmount'      => '',
            'returnLedger'       => 0,
        ]);
        Session::clearFormState();
    }

    public function update(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $receipt = (new Receipt())->findForAssociation((int) $params['id'], $assocId);
        if ($receipt === null) {
            Response::notFound();
        }

        $input = $this->validatedInput($request);

        // A receipt's linked demand is fixed on edit; align member/project to it.
        $demandId = $receipt['demand_id'] ? (int) $receipt['demand_id'] : 0;
        if ($demandId > 0) {
            $demand = (new Demand())->findForAssociation($demandId, $assocId);
            if ($demand !== null) {
                $input['member_id'] = (int) $demand['member_id'];
                if ($demand['project_id']) {
                    $input['project_id'] = (int) $demand['project_id'];
                }
            }
        }

        (new Receipt())->update((int) $receipt['id'], [
            'member_id'       => $input['member_id'],
            'income_head_id'  => $input['income_head_id'],
            'project_id'      => $input['project_id'],
            'amount'          => $input['amount'],
            'mode'            => $input['mode'],
            'bank_account_id' => $input['mode'] === 'fund_transfer' ? $input['bank_account_id'] : ($input['bank_account_id'] ?: null),
            'received_on'     => $input['received_on'],
            'remarks'         => $input['remarks'] ?: null,
        ]);

        if ($demandId > 0) {
            (new Demand())->syncStatus($demandId);
        }

        $this->flash('success', 'Receipt updated.');
        $this->redirect('/receipts');
    }

    /**
     * Extract + validate the shared receipt form fields. Redirects back with
     * errors on failure (never returns in that case).
     *
     * @return array<string,mixed>
     */
    private function validatedInput(Request $request): array
    {
        $assocId = Auth::associationId();
        $input = [
            'member_id'      => $request->input('member_id') ?: null,
            'income_head_id' => $request->input('income_head_id') ?: null,
            'project_id'     => $request->input('project_id') ?: null,
            'demand_id'      => $request->input('demand_id') ?: null,
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
        if ($input['mode'] === 'fund_transfer' && $input['bank_account_id'] === null) {
            $this->withErrors(['bank_account_id' => 'Select the bank account for a fund transfer.'], $input);
        }
        $this->assertTenant($assocId, $input);

        return $input;
    }

    /** @return array{0:?string,1:?string} */
    private function filterDates(Request $request): array
    {
        $from = (string) $request->input('from', '');
        $to = (string) $request->input('to', '');
        return [
            $from !== '' && strtotime($from) ? $from : null,
            $to !== '' && strtotime($to) ? $to : null,
        ];
    }

    public function destroy(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $receipt = (new Receipt())->findForAssociation((int) $params['id'], $assocId);
        if ($receipt === null) {
            Response::notFound();
        }
        $linkedDemand = $receipt['demand_id'] ? (int) $receipt['demand_id'] : 0;
        (new Receipt())->delete((int) $receipt['id']);
        if ($linkedDemand > 0) {
            (new Demand())->syncStatus($linkedDemand);
        }
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
