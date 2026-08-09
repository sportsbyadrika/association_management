<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Gift;
use App\Models\Master;
use App\Models\Member;

/**
 * Gift tracker. Each gift has a direction: "in" (a donation/gift received by
 * the association) or "out" (a gift given by the association).
 */
final class GiftController extends Controller
{
    public function index(Request $request): void
    {
        $assocId = Auth::associationId();
        $page = (int) $request->input('page', 1);
        $direction = (string) $request->input('direction', '');
        $direction = in_array($direction, ['in', 'out'], true) ? $direction : '';
        $search = trim((string) $request->input('q', ''));

        $result = (new Gift())->paginateForAssociation($assocId, $page, 20, $direction, $search);

        $this->view('gifts.index', [
            'title'     => 'Gifts',
            'gifts'     => $result['data'],
            'paginator' => $result,
            'totals'    => (new Gift())->totals($assocId),
            'direction' => $direction,
            'search'    => $search,
        ]);
        Session::clearFormState();
    }

    public function create(Request $request): void
    {
        $assocId = Auth::associationId();
        $this->view('gifts.form', [
            'title'   => 'Record Gift',
            'gift'    => null,
            'types'   => (new Master('gift-types'))->activeForAssociation($assocId),
            'members' => (new Member())->options($assocId),
            'giftMembers' => [],
        ]);
        Session::clearFormState();
    }

    public function store(Request $request): void
    {
        $assocId = Auth::associationId();
        $data = $this->validated($request, $assocId);
        $data['association_id'] = $assocId;
        $data['created_by'] = Auth::id();

        $pairs = $this->memberPairs($request);
        // When members are listed, the gift value is the sum of contributions.
        if ($pairs !== []) {
            $data['value'] = array_sum(array_map(static fn ($p) => $p[1], $pairs));
        }

        $gift = new Gift();
        $id = $gift->create($data);
        $gift->syncMembers($id, $assocId, $pairs);

        $this->flash('success', 'Gift recorded.');
        $this->redirect('/gifts');
    }

    public function show(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $gift = (new Gift())->findWithRelations((int) $params['id'], $assocId);
        if ($gift === null) {
            Response::notFound();
        }
        $this->view('gifts.show', [
            'title' => $gift['title'],
            'gift' => $gift,
            'giftMembers' => (new Gift())->members((int) $gift['id']),
        ]);
    }

    public function edit(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $gift = (new Gift())->findForAssociation((int) $params['id'], $assocId);
        if ($gift === null) {
            Response::notFound();
        }
        $this->view('gifts.form', [
            'title'   => 'Edit Gift',
            'gift'    => $gift,
            'types'   => (new Master('gift-types'))->activeForAssociation($assocId),
            'members' => (new Member())->options($assocId),
            'giftMembers' => (new Gift())->members((int) $gift['id']),
        ]);
        Session::clearFormState();
    }

    public function update(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $gift = (new Gift())->findForAssociation((int) $params['id'], $assocId);
        if ($gift === null) {
            Response::notFound();
        }
        $data = $this->validated($request, $assocId);

        $pairs = $this->memberPairs($request);
        if ($pairs !== []) {
            $data['value'] = array_sum(array_map(static fn ($p) => $p[1], $pairs));
        }

        $model = new Gift();
        $model->update((int) $gift['id'], $data);
        $model->syncMembers((int) $gift['id'], $assocId, $pairs);

        $this->flash('success', 'Gift updated.');
        $this->redirect('/gifts');
    }

    /**
     * Parse the parallel member_ids[]/contributions[] arrays into pairs.
     * @return list<array{0:int,1:float}>
     */
    private function memberPairs(Request $request): array
    {
        $ids = (array) $request->input('gift_member_ids', []);
        $amounts = (array) $request->input('gift_member_contributions', []);
        $pairs = [];
        foreach ($ids as $i => $id) {
            $id = (int) $id;
            if ($id <= 0) {
                continue;
            }
            $amount = isset($amounts[$i]) && $amounts[$i] !== '' ? (float) $amounts[$i] : 0.0;
            $pairs[] = [$id, $amount];
        }
        return $pairs;
    }

    public function destroy(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $gift = (new Gift())->findForAssociation((int) $params['id'], $assocId);
        if ($gift === null) {
            Response::notFound();
        }
        (new Gift())->delete((int) $gift['id']);
        $this->flash('success', 'Gift deleted.');
        $this->redirect('/gifts');
    }

    /** @return array<string,mixed> */
    private function validated(Request $request, int $assocId): array
    {
        $input = [
            'gift_type_id' => $request->input('gift_type_id') ?: null,
            'direction'    => (string) $request->input('direction', 'in'),
            'title'        => (string) $request->input('title', ''),
            'party'        => (string) $request->input('party', ''),
            'member_id'    => $request->input('member_id') ?: null,
            'value'        => (string) $request->input('value', '0'),
            'default_contribution' => (string) $request->input('default_contribution', ''),
            'gift_date'    => (string) $request->input('gift_date', ''),
            'description'  => (string) $request->input('description', ''),
        ];
        $validator = Validator::make($input, [
            'direction' => 'required|in:in,out',
            'title'     => 'required|min:2|max:180',
            'value'     => 'decimal|min_val:0',
            'default_contribution' => 'decimal|min_val:0',
            'gift_date' => 'date',
            'description' => 'max:1000',
        ]);
        if ($validator->fails()) {
            $this->withErrors($validator->errors(), $input);
        }
        if ($input['gift_type_id'] !== null
            && (new Master('gift-types'))->findForAssociation((int) $input['gift_type_id'], $assocId) === null) {
            $this->withErrors(['gift_type_id' => 'Invalid gift type.'], $input);
        }
        if ($input['member_id'] !== null
            && (new Member())->findForAssociation((int) $input['member_id'], $assocId) === null) {
            $this->withErrors(['member_id' => 'Invalid member.'], $input);
        }

        $input['value'] = $input['value'] !== '' ? $input['value'] : '0';
        $input['default_contribution'] = $input['default_contribution'] !== '' ? $input['default_contribution'] : null;
        $input['party'] = $input['party'] ?: null;
        $input['gift_date'] = $input['gift_date'] ?: null;
        $input['description'] = $input['description'] ?: null;
        return $input;
    }
}
