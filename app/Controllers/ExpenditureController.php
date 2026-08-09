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
use App\Models\Event;
use App\Models\Expenditure;
use App\Models\Gift;
use App\Models\Master;
use App\Models\Project;

final class ExpenditureController extends Controller
{
    public function index(Request $request): void
    {
        $assocId = Auth::associationId();
        $page = (int) $request->input('page', 1);

        $projectFilter = (string) $request->input('project_id', '');
        [$from, $to] = $this->filterDates($request);

        $result = (new Expenditure())->paginateForAssociation($assocId, $page, 20, $projectFilter, $from, $to);

        $this->view('expenditures.index', [
            'title'         => 'Expenditure',
            'expenditures'  => $result['data'],
            'paginator'     => $result,
            'projects'      => (new Project())->options($assocId),
            'projectFilter' => $projectFilter,
            'from'          => $from,
            'to'            => $to,
        ]);
        Session::clearFormState();
    }

    public function create(Request $request): void
    {
        $assocId = Auth::associationId();
        $selectedProject = (int) $request->input('project_id', 0);
        // When opened from a project page, remember it so the form can offer a
        // "back to project" link and return there after saving. An explicit
        // back_project (carried by "Save & add another") takes precedence.
        $candidate = (int) $request->input('back_project', 0) ?: $selectedProject;
        $backProject = null;
        if ($candidate > 0 && (new Project())->findForAssociation($candidate, $assocId) !== null) {
            $backProject = $candidate;
        }
        // Presets carried over from "Save & add another" so the user only
        // re-enters amount and remarks.
        $selectedHead = (int) $request->input('expenditure_head_id', 0);
        $paidOn = (string) $request->input('paid_on', '');
        $selectedGift = (int) $request->input('gift_id', 0);
        $selectedEvent = (int) $request->input('event_id', 0);
        $category = (string) $request->input('category', '');
        if ($category === '') {
            $category = $selectedProject > 0 ? 'project'
                : ($selectedGift > 0 ? 'gift' : ($selectedEvent > 0 ? 'event' : 'association'));
        }
        $this->view('expenditures.form', [
            'title'            => 'Record Expenditure',
            'expenditure'      => null,
            'heads'            => (new Master('expenditure-heads'))->activeForAssociation($assocId),
            'projects'         => (new Project())->options($assocId),
            'gifts'            => (new Gift())->options($assocId),
            'events'           => (new Event())->options($assocId),
            'bankAccounts'     => (new BankAccount())->options($assocId),
            'selectedProject'  => $selectedProject,
            'selectedGift'     => $selectedGift,
            'selectedEvent'    => $selectedEvent,
            'selectedCategory' => $category,
            'selectedHead'     => $selectedHead,
            'selectedPaidOn'   => $paidOn !== '' && strtotime($paidOn) ? $paidOn : null,
            'backProject'      => $backProject,
        ]);
        Session::clearFormState();
    }

    public function store(Request $request): void
    {
        $assocId = Auth::associationId();
        $input = $this->validatedInput($request);

        (new Expenditure())->create(array_merge([
            'association_id'      => $assocId,
            'expenditure_head_id' => $input['expenditure_head_id'],
            'category'            => $input['category'],
            'amount'              => $input['amount'],
            'paid_on'             => $input['paid_on'],
            'bank_account_id'     => $input['bank_account_id'],
            'mode'                => $input['mode'],
            'remarks'             => $input['remarks'] ?: null,
            'created_by'          => Auth::id(),
        ], $this->categoryLinks($input)));

        // "Save & add another": reopen the form preserving category, project,
        // head and date so only amount + remarks need re-entry.
        if ($this->wantsSaveNew($request)) {
            $this->flash('success', 'Expenditure saved. Enter the next one.');
            $this->redirect('/expenditures/create' . $this->presetQuery($request, $input));
        }

        $this->flash('success', 'Expenditure recorded.');

        // If the entry was raised from a project page, return there.
        $backProject = (int) $request->input('back_project', 0);
        if ($backProject > 0 && (new Project())->findForAssociation($backProject, $assocId) !== null) {
            $this->redirect('/projects/' . $backProject);
        }
        $this->redirect('/expenditures');
    }

    public function edit(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $exp = (new Expenditure())->findForAssociation((int) $params['id'], $assocId);
        if ($exp === null) {
            Response::notFound();
        }
        $this->view('expenditures.form', [
            'title'            => 'Edit Expenditure',
            'expenditure'      => $exp,
            'heads'            => (new Master('expenditure-heads'))->activeForAssociation($assocId),
            'projects'         => (new Project())->options($assocId),
            'gifts'            => (new Gift())->options($assocId),
            'events'           => (new Event())->options($assocId),
            'bankAccounts'     => (new BankAccount())->options($assocId),
            'selectedProject'  => (int) ($exp['project_id'] ?? 0),
            'selectedGift'     => (int) ($exp['gift_id'] ?? 0),
            'selectedEvent'    => (int) ($exp['event_id'] ?? 0),
            'selectedCategory' => (string) $exp['category'],
        ]);
        Session::clearFormState();
    }

    public function update(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $exp = (new Expenditure())->findForAssociation((int) $params['id'], $assocId);
        if ($exp === null) {
            Response::notFound();
        }
        $input = $this->validatedInput($request);

        (new Expenditure())->update((int) $exp['id'], array_merge([
            'expenditure_head_id' => $input['expenditure_head_id'],
            'category'            => $input['category'],
            'amount'              => $input['amount'],
            'paid_on'             => $input['paid_on'],
            'bank_account_id'     => $input['bank_account_id'],
            'mode'                => $input['mode'],
            'remarks'             => $input['remarks'] ?: null,
        ], $this->categoryLinks($input)));

        if ($this->wantsSaveNew($request)) {
            $this->flash('success', 'Expenditure updated. Enter the next one.');
            $this->redirect('/expenditures/create' . $this->presetQuery($request, $input));
        }

        $this->flash('success', 'Expenditure updated.');
        $this->redirect('/expenditures');
    }

    private function wantsSaveNew(Request $request): bool
    {
        $v = $request->input('save_new');
        return $v !== null && $v !== '';
    }

    /**
     * Build the /expenditures/create query string that carries category (via
     * project_id), expenditure head and paid-on date over to the next entry.
     * @param array<string,mixed> $input
     */
    private function presetQuery(Request $request, array $input): string
    {
        $params = array_filter([
            'category'            => $input['category'],
            'project_id'          => $input['category'] === 'project' ? $input['project_id'] : null,
            'gift_id'             => $input['category'] === 'gift' ? $input['gift_id'] : null,
            'event_id'            => $input['category'] === 'event' ? $input['event_id'] : null,
            'expenditure_head_id' => $input['expenditure_head_id'],
            'paid_on'             => $input['paid_on'],
            'back_project'        => ((int) $request->input('back_project', 0)) ?: null,
        ], static fn ($v) => $v !== null && $v !== '');
        $qs = http_build_query($params);
        return $qs !== '' ? '?' . $qs : '';
    }

    /**
     * Validate + normalise the shared expenditure form input. On failure this
     * redirects back with errors (via withErrors) and never returns.
     *
     * @return array<string,mixed>
     */
    private function validatedInput(Request $request): array
    {
        $assocId = Auth::associationId();
        $input = [
            'expenditure_head_id' => $request->input('expenditure_head_id') ?: null,
            'project_id' => $request->input('project_id') ?: null,
            'gift_id'    => $request->input('gift_id') ?: null,
            'event_id'   => $request->input('event_id') ?: null,
            'category'   => (string) $request->input('category', 'association'),
            'amount'     => (string) $request->input('amount', ''),
            'paid_on'    => (string) $request->input('paid_on', ''),
            'bank_account_id' => $request->input('bank_account_id') ?: null,
            'mode'       => (string) $request->input('mode', 'cash'),
            'remarks'    => (string) $request->input('remarks', ''),
        ];
        $validator = Validator::make($input, [
            'category' => 'required|in:project,association,gift,event',
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
        if ($input['category'] === 'gift' && $input['gift_id'] === null) {
            $this->withErrors(['gift_id' => 'Select the gift this expense belongs to.'], $input);
        }
        if ($input['category'] === 'event' && $input['event_id'] === null) {
            $this->withErrors(['event_id' => 'Select the event this expense belongs to.'], $input);
        }
        if ($input['mode'] === 'fund_transfer' && $input['bank_account_id'] === null) {
            $this->withErrors(['bank_account_id' => 'Select the bank account for a fund transfer.'], $input);
        }
        $this->assertTenant($assocId, $input);

        return $input;
    }

    /**
     * Map the category to the correct link column, nulling the others.
     * @param array<string,mixed> $input
     * @return array{project_id:?int,gift_id:?int,event_id:?int}
     */
    private function categoryLinks(array $input): array
    {
        return [
            'project_id' => $input['category'] === 'project' ? $input['project_id'] : null,
            'gift_id'    => $input['category'] === 'gift' ? $input['gift_id'] : null,
            'event_id'   => $input['category'] === 'event' ? $input['event_id'] : null,
        ];
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

    private function assertTenant(int $assocId, array $input): void
    {
        if ($input['expenditure_head_id'] !== null && (new Master('expenditure-heads'))->findForAssociation((int) $input['expenditure_head_id'], $assocId) === null) {
            $this->withErrors(['expenditure_head_id' => 'Invalid expenditure head.'], $input);
        }
        if ($input['project_id'] !== null && (new Project())->findForAssociation((int) $input['project_id'], $assocId) === null) {
            $this->withErrors(['project_id' => 'Invalid project.'], $input);
        }
        if (($input['gift_id'] ?? null) !== null && (new Gift())->findForAssociation((int) $input['gift_id'], $assocId) === null) {
            $this->withErrors(['gift_id' => 'Invalid gift.'], $input);
        }
        if (($input['event_id'] ?? null) !== null && (new Event())->findForAssociation((int) $input['event_id'], $assocId) === null) {
            $this->withErrors(['event_id' => 'Invalid event.'], $input);
        }
        if ($input['bank_account_id'] !== null && (new BankAccount())->findForAssociation((int) $input['bank_account_id'], $assocId) === null) {
            $this->withErrors(['bank_account_id' => 'Invalid bank account.'], $input);
        }
    }
}
