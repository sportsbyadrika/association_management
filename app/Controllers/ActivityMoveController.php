<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Master;
use App\Services\ActivityMover;

/**
 * Wizard to convert an activity (project / gift / event) into another type,
 * carrying its receipts and expenditures across.
 */
final class ActivityMoveController extends Controller
{
    private const MASTER = ['project' => 'project-types', 'gift' => 'gift-types', 'event' => 'event-types'];
    private const ROUTE = ['project' => '/projects', 'gift' => '/gifts', 'event' => '/events'];

    /** Step 1 — choose the destination type. */
    public function chooseDestination(Request $request, array $params): void
    {
        [$from, $id, $src] = $this->loadSource($params);

        $this->view('activities.move_step1', [
            'title'        => 'Move ' . ActivityMover::label($from),
            'from'         => $from,
            'id'           => $id,
            'name'         => $src['name'] ?? $src['title'] ?? 'Untitled',
            'destinations' => array_values(array_filter(ActivityMover::TYPES, static fn ($t) => $t !== $from)),
            'backUrl'      => self::ROUTE[$from] . '/' . $id,
        ]);
    }

    /** Step 2 — review the impact and set destination-only fields. */
    public function review(Request $request, array $params): void
    {
        [$from, $id, $src] = $this->loadSource($params);
        $to = (string) ($params['to'] ?? '');
        if ($to === $from || !in_array($to, ActivityMover::TYPES, true)) {
            Response::notFound('Invalid destination.');
        }

        $this->view('activities.move_step2', [
            'title'   => 'Move to ' . ActivityMover::label($to),
            'from'    => $from,
            'to'      => $to,
            'id'      => $id,
            'name'    => $src['name'] ?? $src['title'] ?? 'Untitled',
            'impact'  => (new ActivityMover())->impact($from, $id, Auth::associationId()),
            'types'   => (new Master(self::MASTER[$to]))->activeForAssociation(Auth::associationId()),
            'backUrl' => self::ROUTE[$from] . '/' . $id . '/move',
        ]);
    }

    /** Perform the conversion. */
    public function perform(Request $request, array $params): void
    {
        [$from, $id] = $this->loadSource($params);
        $to = (string) ($params['to'] ?? '');
        if ($to === $from || !in_array($to, ActivityMover::TYPES, true)) {
            Response::notFound('Invalid destination.');
        }

        $extra = [
            'type_id'   => $request->input('type_id') ?: null,
            'direction' => (string) $request->input('direction', 'in'),
        ];

        try {
            $newId = (new ActivityMover())->move($from, $id, $to, $extra, Auth::associationId());
        } catch (\Throwable $e) {
            $this->flash('error', 'Could not move: ' . $e->getMessage());
            $this->redirect(self::ROUTE[$from] . '/' . $id);
        }

        $this->flash('success', ActivityMover::label($from) . ' moved to ' . ActivityMover::label($to) . '.');
        $this->redirect(self::ROUTE[$to] . '/' . $newId);
    }

    /** @return array{0:string,1:int,2:array<string,mixed>} */
    private function loadSource(array $params): array
    {
        $from = (string) ($params['from'] ?? '');
        $id = (int) ($params['id'] ?? 0);
        if (!in_array($from, ActivityMover::TYPES, true)) {
            Response::notFound('Unknown activity type.');
        }
        $src = (new ActivityMover())->find($from, $id, Auth::associationId());
        if ($src === null) {
            Response::notFound();
        }
        return [$from, $id, $src];
    }
}
