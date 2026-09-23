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
use App\Models\DemandPurpose;
use App\Models\Event;
use App\Models\FinancialYear;
use App\Models\Gift;
use App\Models\Member;
use App\Models\Project;
use App\Models\Receipt;

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
            'title'          => 'Dues',
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

        // A due is raised for a Subscription or an Activity (project/gift/event).
        // Opening from an activity page preselects it.
        $presetProject = (int) $request->input('project_id', 0);
        if ($presetProject > 0 && (new Project())->findForAssociation($presetProject, $assocId) === null) {
            $presetProject = 0;
        }
        $presetGift = (int) $request->input('gift_id', 0);
        if ($presetGift > 0 && (new Gift())->findForAssociation($presetGift, $assocId) === null) {
            $presetGift = 0;
        }
        $presetEvent = (int) $request->input('event_id', 0);
        if ($presetEvent > 0 && (new Event())->findForAssociation($presetEvent, $assocId) === null) {
            $presetEvent = 0;
        }
        $presetCategory = $presetProject > 0 ? 'project'
            : ($presetGift > 0 ? 'gift' : ($presetEvent > 0 ? 'event' : 'subscription'));

        $this->view('demands.form', [
            'title'           => 'Raise Due',
            'members'         => (new Member())->selectableForAssociation($assocId),
            'memberTypes'     => (new \App\Models\Master('member-types'))->activeForAssociation($assocId),
            'projects'        => (new Project())->options($assocId),
            'gifts'           => (new Gift())->options($assocId),
            'events'          => (new Event())->options($assocId),
            'preselected'     => $preselected,
            'existingDemands' => (new Demand())->projectMemberMap($assocId),
            'presetCategory'  => $presetCategory,
            'presetProject'   => $presetProject,
            'presetGift'      => $presetGift,
            'presetEvent'     => $presetEvent,
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

        // A human label for what the due is for.
        $forCategory = ['subscription' => 'Subscription', 'project' => 'Project', 'gift' => 'Gift', 'event' => 'Event'][$details['category']] ?? 'Due';
        $forName = null;
        if ($details['category'] === 'project' && $details['project_id']) {
            $forName = (new Project())->findForAssociation((int) $details['project_id'], $assocId)['name'] ?? null;
        } elseif ($details['category'] === 'gift' && $details['gift_id']) {
            $forName = (new Gift())->findForAssociation((int) $details['gift_id'], $assocId)['title'] ?? null;
        } elseif ($details['category'] === 'event' && $details['event_id']) {
            $forName = (new Event())->findForAssociation((int) $details['event_id'], $assocId)['title'] ?? null;
        }

        $this->view('demands.confirm', [
            'title'         => 'Confirm Dues',
            'details'       => $details,
            'members'       => $members,
            'forCategory'   => $forCategory,
            'forName'       => $forName,
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
                    'association_id'    => $assocId,
                    'member_id'         => $id,
                    'demand_purpose_id' => $details['demand_purpose_id'],
                    'project_id'        => $details['project_id'],
                    'gift_id'           => $details['gift_id'],
                    'event_id'          => $details['event_id'],
                    'amount'            => $amounts[$id],
                    'due_date'          => $details['due_date'] ?: null,
                    'status'            => 'pending',
                    'remarks'           => $details['remarks'] ?: null,
                    'created_by'        => Auth::id(),
                ]);
                $total += (float) $amounts[$id];
                $n++;
            }
            return $n;
        });

        $this->flash('success', "{$count} due(s) raised — total ₹" . number_format($total, 2) . '.');
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
            $this->flash('error', 'A cancelled due cannot be marked as paid.');
        } else {
            (new Demand())->update((int) $demand['id'], ['status' => 'paid']);
            $this->flash('success', 'Due marked as paid.');
        }
        $this->back('/demands');
    }

    /**
     * Reopen a demand that was marked paid by mistake. Its status is
     * recomputed from actual receipts (pending / partial). Demands genuinely
     * covered by receipts cannot be reopened here — remove the receipt instead.
     */
    public function reopen(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $demandModel = new Demand();
        $demand = $demandModel->findForAssociation((int) $params['id'], $assocId);
        if ($demand === null) {
            Response::notFound();
        }
        if ($demand['status'] !== 'paid') {
            $this->flash('error', 'Only a paid due can be reopened.');
            $this->back('/demands');
        }
        $paid = (new Receipt())->totalForDemand((int) $demand['id']);
        if ($paid >= (float) $demand['amount']) {
            $this->flash('warning', 'This due is fully covered by receipts — delete the receipt(s) to reopen it.');
            $this->back('/demands');
        }
        // Manually marked paid: recompute from receipts (partial or pending).
        $demandModel->syncStatus((int) $demand['id']);
        $this->flash('success', 'Due reopened.');
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
        $this->flash('success', 'Due cancelled.');
        $this->back('/demands');
    }

    // ---- Shared validation ---------------------------------------------

    /** @return array{category:string,demand_purpose_id:?int,project_id:?int,gift_id:?int,event_id:?int,amount:string,due_date:string,remarks:string} */
    private function validateDetails(Request $request): array
    {
        $assocId = Auth::associationId();
        $input = [
            'category'   => (string) $request->input('category', 'subscription'),
            'project_id' => $request->input('project_id') ?: null,
            'gift_id'    => $request->input('gift_id') ?: null,
            'event_id'   => $request->input('event_id') ?: null,
            'amount'     => (string) $request->input('amount', ''),
            'due_date'   => (string) $request->input('due_date', ''),
            'remarks'    => (string) $request->input('remarks', ''),
        ];
        $validator = Validator::make($input, [
            'category' => 'required|in:subscription,project,gift,event',
            'amount'   => 'required|decimal|min_val:0.01',
            'due_date' => 'date',
            'remarks'  => 'max:500',
        ], ['category' => 'Due for']);
        if ($validator->fails()) {
            $this->withErrors($validator->errors(), $input);
        }

        // Resolve the linked activity for the chosen category, tenant-checked.
        $category = $input['category'];
        $projectId = $giftId = $eventId = null;
        $purposeModel = new DemandPurpose();
        $purposeId = null;

        if ($category === 'subscription') {
            $purposeId = $purposeModel->subscriptionId($assocId);
        } elseif ($category === 'project') {
            $projectId = (int) ($input['project_id'] ?? 0);
            if ($projectId <= 0 || (new Project())->findForAssociation($projectId, $assocId) === null) {
                $this->withErrors(['project_id' => 'Please select a valid project.'], $input);
            }
            $purposeId = $purposeModel->idByName($assocId, 'Project Contribution');
        } elseif ($category === 'gift') {
            $giftId = (int) ($input['gift_id'] ?? 0);
            if ($giftId <= 0 || (new Gift())->findForAssociation($giftId, $assocId) === null) {
                $this->withErrors(['gift_id' => 'Please select a valid gift.'], $input);
            }
        } elseif ($category === 'event') {
            $eventId = (int) ($input['event_id'] ?? 0);
            if ($eventId <= 0 || (new Event())->findForAssociation($eventId, $assocId) === null) {
                $this->withErrors(['event_id' => 'Please select a valid event.'], $input);
            }
        }

        return [
            'category'          => $category,
            'demand_purpose_id' => $purposeId,
            'project_id'        => $projectId,
            'gift_id'           => $giftId,
            'event_id'          => $eventId,
            'amount'            => $input['amount'],
            'due_date'          => $input['due_date'],
            'remarks'           => $input['remarks'],
        ];
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
