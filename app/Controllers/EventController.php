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
        ]);
        Session::clearFormState();
    }

    public function store(Request $request): void
    {
        $assocId = Auth::associationId();
        $data = $this->validated($request, $assocId);
        $data['association_id'] = $assocId;
        $data['created_by'] = Auth::id();

        $id = (new Event())->create($data);
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
            'title'     => $event['title'],
            'event'     => $event,
            'spent'     => $model->spent((int) $event['id']),
            'collected' => $model->collected((int) $event['id']),
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
        (new Event())->update((int) $event['id'], $data);
        $this->flash('success', 'Event updated.');
        $this->redirect('/events/' . $event['id']);
    }

    public function destroy(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $event = (new Event())->findForAssociation((int) $params['id'], $assocId);
        if ($event === null) {
            Response::notFound();
        }
        (new Event())->delete((int) $event['id']);
        $this->flash('success', 'Event deleted.');
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
            'description'        => (string) $request->input('description', ''),
        ];
        $validator = Validator::make($input, [
            'title'              => 'required|min:2|max:180',
            'status'             => 'required|in:planned,completed,cancelled',
            'value'              => 'decimal|min_val:0',
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
        foreach (['venue', 'location', 'start_date', 'end_date', 'registration_start', 'registration_end', 'description'] as $k) {
            $input[$k] = $input[$k] !== '' ? $input[$k] : null;
        }
        return $input;
    }
}
