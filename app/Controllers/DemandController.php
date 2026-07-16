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
use App\Models\Member;
use App\Models\Project;

final class DemandController extends Controller
{
    public function index(Request $request): void
    {
        $assocId = Auth::associationId();
        $page = (int) $request->input('page', 1);
        $result = (new Demand())->paginateForAssociation($assocId, $page, 20);

        $this->view('demands.index', [
            'title'     => 'Demands',
            'demands'   => $result['data'],
            'paginator' => $result,
        ]);
        Session::clearFormState();
    }

    public function create(Request $request): void
    {
        $assocId = Auth::associationId();
        $this->view('demands.form', [
            'title'      => 'Raise Demand',
            'members'    => (new Member())->options($assocId),
            'projects'   => (new Project())->options($assocId),
            'selectedMember' => (int) $request->input('member_id', 0),
        ]);
        Session::clearFormState();
    }

    public function store(Request $request): void
    {
        $assocId = Auth::associationId();
        $input = [
            'member_id' => (int) $request->input('member_id', 0),
            'purpose'   => (string) $request->input('purpose', 'subscription'),
            'project_id' => $request->input('project_id') ?: null,
            'amount'    => (string) $request->input('amount', ''),
            'due_date'  => (string) $request->input('due_date', ''),
            'remarks'   => (string) $request->input('remarks', ''),
        ];
        $validator = Validator::make($input, [
            'member_id' => 'required|integer',
            'purpose'   => 'required|in:subscription,project,other',
            'amount'    => 'required|decimal|min_val:0.01',
            'due_date'  => 'date',
            'remarks'   => 'max:500',
        ]);
        if ($validator->fails()) {
            $this->withErrors($validator->errors(), $input);
        }

        // Tenant isolation: the member must belong to this association.
        if ((new Member())->findForAssociation($input['member_id'], $assocId) === null) {
            $this->withErrors(['member_id' => 'Please select a valid member.'], $input);
        }
        if ($input['project_id'] !== null && (new Project())->findForAssociation((int) $input['project_id'], $assocId) === null) {
            $this->withErrors(['project_id' => 'Please select a valid project.'], $input);
        }

        (new Demand())->create([
            'association_id' => $assocId,
            'member_id'  => $input['member_id'],
            'purpose'    => $input['purpose'],
            'project_id' => $input['purpose'] === 'project' ? $input['project_id'] : null,
            'amount'     => $input['amount'],
            'due_date'   => $input['due_date'] ?: null,
            'status'     => 'pending',
            'remarks'    => $input['remarks'] ?: null,
            'created_by' => Auth::id(),
        ]);

        $this->flash('success', 'Demand raised.');
        $this->redirect('/demands');
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
        $this->redirect('/demands');
    }
}
