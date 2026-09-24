<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Event;
use App\Models\Master;
use App\Models\Member;
use App\Services\ImageUploader;

/**
 * Events tracker (with an Event Type master). Events can be linked from
 * receipts and expenditures via their "Event" category.
 */
final class EventController extends Controller
{
    public function index(Request $request): void
    {
        $assocId = Auth::associationId();
        $page = (int) $request->input('page', 1);
        $search = trim((string) $request->input('q', ''));

        $result = (new Event())->paginateForAssociation($assocId, $page, 20, $search);

        $this->view('events.index', [
            'title'     => 'Events',
            'events'    => $result['data'],
            'paginator' => $result,
            'search'    => $search,
        ]);
        Session::clearFormState();
    }

    public function create(Request $request): void
    {
        $assocId = Auth::associationId();
        $this->view('events.form', [
            'title' => 'Add Event',
            'event' => null,
            'types' => (new Master('event-types'))->activeForAssociation($assocId),
            'members' => (new Member())->options($assocId),
            'eventMembers' => [],
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
        if ($pairs !== []) {
            $data['value'] = array_sum(array_map(static fn ($p) => $p[1], $pairs));
        }

        $model = new Event();
        $id = $model->create($data);
        $model->syncMembers($id, $assocId, $pairs);

        $this->flash('success', 'Event created.');
        $this->redirect('/events/' . $id);
    }

    public function show(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $event = (new Event())->findWithType((int) $params['id'], $assocId);
        if ($event === null) {
            Response::notFound();
        }
        $model = new Event();
        $this->view('events.show', [
            'title'        => $event['title'],
            'event'        => $event,
            'spent'        => $model->spent((int) $event['id']),
            'collected'    => $model->collected((int) $event['id']),
            'eventMembers' => $model->members((int) $event['id']),
            'collections'  => $model->collectionList((int) $event['id']),
            'expenditures' => $model->expenditureList((int) $event['id']),
        ]);
    }

    public function edit(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $event = (new Event())->findForAssociation((int) $params['id'], $assocId);
        if ($event === null) {
            Response::notFound();
        }
        $this->view('events.form', [
            'title' => 'Edit Event',
            'event' => $event,
            'types' => (new Master('event-types'))->activeForAssociation($assocId),
            'members' => (new Member())->options($assocId),
            'eventMembers' => (new Event())->members((int) $event['id']),
        ]);
        Session::clearFormState();
    }

    public function update(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $event = (new Event())->findForAssociation((int) $params['id'], $assocId);
        if ($event === null) {
            Response::notFound();
        }
        $data = $this->validated($request, $assocId);

        $pairs = $this->memberPairs($request);
        if ($pairs !== []) {
            $data['value'] = array_sum(array_map(static fn ($p) => $p[1], $pairs));
        }

        $model = new Event();
        $model->update((int) $event['id'], $data);
        $model->syncMembers((int) $event['id'], $assocId, $pairs);

        $this->flash('success', 'Event updated.');
        $this->redirect('/events/' . $event['id']);
    }

    /** Upload (or replace) the event's display image. */
    public function uploadImage(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $model = new Event();
        $event = $model->findForAssociation((int) $params['id'], $assocId);
        if ($event === null) {
            Response::notFound();
        }
        $file = $request->file('image');
        if ($file === null) {
            $this->flash('error', 'Please choose an image to upload.');
            $this->redirect('/events/' . $event['id']);
        }
        $uploader = new ImageUploader();
        try {
            $path = $uploader->store($file, 'events');
        } catch (\RuntimeException $e) {
            $this->flash('error', $e->getMessage());
            $this->redirect('/events/' . $event['id']);
        }
        $uploader->delete($event['image_path'] ?? null);
        $model->update((int) $event['id'], ['image_path' => $path]);
        $this->flash('success', 'Event image updated.');
        $this->redirect('/events/' . $event['id']);
    }

    /** Remove the event's display image. */
    public function removeImage(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $model = new Event();
        $event = $model->findForAssociation((int) $params['id'], $assocId);
        if ($event === null) {
            Response::notFound();
        }
        (new ImageUploader())->delete($event['image_path'] ?? null);
        $model->update((int) $event['id'], ['image_path' => null]);
        $this->flash('success', 'Event image removed.');
        $this->redirect('/events/' . $event['id']);
    }

    /**
     * Parse the parallel event_member_ids[]/contributions[] arrays into pairs.
     * @return list<array{0:int,1:float}>
     */
    private function memberPairs(Request $request): array
    {
        $ids = (array) $request->input('event_member_ids', []);
        $amounts = (array) $request->input('event_member_contributions', []);
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
        $eventModel = new Event();
        $event = $eventModel->findForAssociation((int) $params['id'], $assocId);
        if ($event === null) {
            Response::notFound();
        }
        $eventId = (int) $event['id'];
        // Events carry no FKs: unlink receipts/expenditures first so their money
        // is preserved as general entries instead of being orphaned (which would
        // hide it from reports).
        $db = $eventModel->db();
        $db->transaction(function () use ($db, $eventId, $assocId, $eventModel): void {
            $db->run('UPDATE receipts SET event_id = NULL, category = ? WHERE event_id = ? AND association_id = ?', ['general', $eventId, $assocId]);
            $db->run('UPDATE expenditures SET event_id = NULL, category = ? WHERE event_id = ? AND association_id = ?', ['association', $eventId, $assocId]);
            $eventModel->delete($eventId);
        });
        $this->flash('success', 'Event deleted. Any linked receipts and expenditures were kept as general entries.');
        $this->redirect('/events');
    }

    /** @return array<string,mixed> */
    private function validated(Request $request, int $assocId): array
    {
        $input = [
            'event_type_id'      => $request->input('event_type_id') ?: null,
            'title'              => (string) $request->input('title', ''),
            'venue'              => (string) $request->input('venue', ''),
            'location'           => (string) $request->input('location', ''),
            'start_date'         => (string) $request->input('start_date', ''),
            'end_date'           => (string) $request->input('end_date', ''),
            'registration_start' => (string) $request->input('registration_start', ''),
            'registration_end'   => (string) $request->input('registration_end', ''),
            'status'             => (string) $request->input('status', 'planned'),
            'value'              => (string) $request->input('value', '0'),
            'default_contribution' => (string) $request->input('default_contribution', ''),
            'description'        => (string) $request->input('description', ''),
        ];
        $validator = Validator::make($input, [
            'title'              => 'required|min:2|max:180',
            'status'             => 'required|in:planned,completed,cancelled',
            'value'              => 'decimal|min_val:0',
            'default_contribution' => 'decimal|min_val:0',
            'start_date'         => 'date',
            'end_date'           => 'date',
            'registration_start' => 'date',
            'registration_end'   => 'date',
            'description'        => 'max:1000',
        ]);
        if ($validator->fails()) {
            $this->withErrors($validator->errors(), $input);
        }
        if ($input['event_type_id'] !== null
            && (new Master('event-types'))->findForAssociation((int) $input['event_type_id'], $assocId) === null) {
            $this->withErrors(['event_type_id' => 'Invalid event type.'], $input);
        }

        $input['value'] = $input['value'] !== '' ? $input['value'] : '0';
        $input['default_contribution'] = $input['default_contribution'] !== '' ? $input['default_contribution'] : null;
        foreach (['venue', 'location', 'start_date', 'end_date', 'registration_start', 'registration_end', 'description'] as $k) {
            $input[$k] = $input[$k] !== '' ? $input[$k] : null;
        }
        return $input;
    }
}
