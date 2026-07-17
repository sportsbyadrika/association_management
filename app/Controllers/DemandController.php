<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Demand;
use App\Models\FinancialYear;
use App\Models\Member;
use App\Models\Project;

final class DemandController extends Controller
{
    public function index(Request $request): void
    {
        $assocId = Auth::associationId();
        $page = (int) $request->input('page', 1);
        $search = trim((string) $request->input('q', ''));

        $fyModel = new FinancialYear();
        $financialYears = $fyModel->allForAssociationOrdered($assocId);

        // Resolve the selected financial year. Default to the current one.
        $fyParam = $request->input('fy');
        $selectedFy = null;
        if ($fyParam === 'all') {
            $selectedFy = null;
        } elseif ($fyParam !== null && $fyParam !== '') {
            foreach ($financialYears as $fy) {
                if ((int) $fy['id'] === (int) $fyParam) {
                    $selectedFy = $fy;
                    break;
                }
            }
        } else {
            $selectedFy = $fyModel->current($assocId); // default: current FY
        }

        $result = (new Demand())->paginateForAssociation(
            $assocId,
            $search,
            $selectedFy['start_date'] ?? null,
            $selectedFy['end_date'] ?? null,
            $page,
            20
        );

        $this->view('demands.index', [
            'title'          => 'Demands',
            'demands'        => $result['data'],
            'paginator'      => $result,
            'search'         => $search,
            'financialYears' => $financialYears,
            'selectedFy'     => $selectedFy,
            'fyParam'        => $fyParam,
        ]);
        Session::clearFormState();
    }

    /**
     * Raise Demand — two-column page: details on the left, a searchable
     * member-selection table on the right.
     */
    public function create(Request $request): void
    {
        $assocId = Auth::associationId();
        $preselected = [];
        $pre = (int) $request->input('member_id', 0);
        if ($pre > 0) {
            $preselected[] = $pre;
        }

        $this->view('demands.form', [
            'title'           => 'Raise Demand',
            'members'         => (new Member())->selectableForAssociation($assocId),
            'projects'        => (new Project())->options($assocId),
            'preselected'     => $preselected,
            'existingDemands' => (new Demand())->projectMemberMap($assocId),
        ]);
        Session::clearFormState();
    }

    /**
     * Step 2 — confirmation: show the demand details + the selected members
     * before anything is written.
     */
    public function preview(Request $request): void
    {
        $assocId = Auth::associationId();
        $details = $this->validateDetails($request);
        $members = $this->resolveMembers($request, $assocId);

        $memberAmounts = [];
        foreach ($members as $m) {
            $memberAmounts[(int) $m['id']] = $details['amount'];
        }

        $this->renderConfirm($details, $members, $memberAmounts);
    }

    private function renderConfirm(array $details, array $members, array $memberAmounts, array $invalidIds = [], ?string $error = null): void
    {
        $assocId = Auth::associationId();
        $projectName = null;
        if ($details['purpose'] === 'project' && $details['project_id'] !== null) {
            $project = (new Project())->findForAssociation((int) $details['project_id'], $assocId);
            $projectName = $project['name'] ?? null;
        }

        $this->view('demands.confirm', [
            'title'         => 'Confirm Demands',
            'details'       => $details,
            'members'       => $members,
            'projectName'   => $projectName,
            'memberAmounts' => $memberAmounts,
            'invalidIds'    => $invalidIds,
            'error'         => $error,
        ]);
    }

    /**
     * Step 3 — create one demand per selected member, in a transaction.
     */
    public function bulkStore(Request $request): void
    {
        $assocId = Auth::associationId();
        $details = $this->validateDetails($request);
        $members = $this->resolveMembers($request, $assocId);

        // Per-member amount overrides (fall back to the base amount).
        $overrides = $request->input('amounts', []);
        if (!is_array($overrides)) {
            $overrides = [];
        }
        $amounts = [];
        $invalid = [];
        foreach ($members as $m) {
            $id = (int) $m['id'];
            $raw = isset($overrides[$id]) ? trim((string) $overrides[$id]) : '';
            $value = $raw === '' ? (string) $details['amount'] : $raw;
            if (!preg_match('/^\d{1,10}(\.\d{1,2})?$/', $value) || (float) $value <= 0) {
                $invalid[] = $id;
            }
            $amounts[$id] = $value;
        }

        if ($invalid !== []) {
            $this->renderConfirm($details, $members, $amounts, $invalid,
                'Some amounts are invalid. Each amount must be a number greater than zero.');
            return;
        }

        $demand = new Demand();
        $total = 0.0;
        $count = $demand->db()->transaction(function () use ($demand, $members, $details, $assocId, $amounts, &$total): int {
            $n = 0;
            foreach ($members as $m) {
                $id = (int) $m['id'];
                $demand->create([
                    'association_id' => $assocId,
                    'member_id'  => $id,
                    'purpose'    => $details['purpose'],
                    'project_id' => $details['purpose'] === 'project' ? $details['project_id'] : null,
                    'amount'     => $amounts[$id],
                    'due_date'   => $details['due_date'] ?: null,
                    'status'     => 'pending',
                    'remarks'    => $details['remarks'] ?: null,
                    'created_by' => Auth::id(),
                ]);
                $total += (float) $amounts[$id];
                $n++;
            }
            return $n;
        });

        $this->flash('success', "{$count} demand(s) raised — total ₹" . number_format($total, 2) . '.');
        $this->redirect('/demands');
    }

    /**
     * Manually mark a demand as paid without recording a receipt
     * (e.g. paid in kind, waived, or reconciled outside the system).
     */
    public function markPaid(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $demand = (new Demand())->findForAssociation((int) $params['id'], $assocId);
        if ($demand === null) {
            Response::notFound();
        }
        if ($demand['status'] === 'cancelled') {
            $this->flash('error', 'A cancelled demand cannot be marked as paid.');
        } else {
            (new Demand())->update((int) $demand['id'], ['status' => 'paid']);
            $this->flash('success', 'Demand marked as paid.');
        }
        $this->back('/demands');
    }

    public function destroy(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $demand = (new Demand())->findForAssociation((int) $params['id'], $assocId);
        if ($demand === null) {
            Response::notFound();
        }
        // Cancel rather than hard-delete to preserve history.
        (new Demand())->update((int) $demand['id'], ['status' => 'cancelled']);
        $this->flash('success', 'Demand cancelled.');
        $this->back('/demands');
    }

    // ---- Shared validation ---------------------------------------------

    /** @return array{purpose:string,project_id:?string,amount:string,due_date:string,remarks:string} */
    private function validateDetails(Request $request): array
    {
        $assocId = Auth::associationId();
        $input = [
            'purpose'    => (string) $request->input('purpose', 'subscription'),
            'project_id' => $request->input('project_id') ?: null,
            'amount'     => (string) $request->input('amount', ''),
            'due_date'   => (string) $request->input('due_date', ''),
            'remarks'    => (string) $request->input('remarks', ''),
        ];
        $validator = Validator::make($input, [
            'purpose'  => 'required|in:subscription,project,other',
            'amount'   => 'required|decimal|min_val:0.01',
            'due_date' => 'date',
            'remarks'  => 'max:500',
        ]);
        if ($validator->fails()) {
            $this->withErrors($validator->errors(), $input);
        }

        if ($input['purpose'] === 'project') {
            if ($input['project_id'] === null || (new Project())->findForAssociation((int) $input['project_id'], $assocId) === null) {
                $this->withErrors(['project_id' => 'Please select a valid project for a project demand.'], $input);
            }
        } else {
            $input['project_id'] = null;
        }
        return $input;
    }

    /**
     * Resolve + tenant-check the selected member ids.
     * @return list<array<string,mixed>>
     */
    private function resolveMembers(Request $request, int $assocId): array
    {
        $ids = $request->input('member_ids', []);
        if (!is_array($ids)) {
            $ids = [];
        }
        $members = (new Member())->findManyForAssociation($ids, $assocId);
        if ($members === []) {
            Session::flash('error', 'Please select at least one member.');
            $this->redirect('/demands/create');
        }
        return $members;
    }
}
