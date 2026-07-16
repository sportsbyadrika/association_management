<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Association;

/**
 * Super Admin: manage an association's subscription period + active status.
 */
final class SubscriptionController extends Controller
{
    public function edit(Request $request, array $params): void
    {
        $association = (new Association())->find((int) $params['id']);
        if ($association === null) {
            Response::notFound();
        }
        $this->view('admin.associations.subscription', [
            'title' => 'Subscription — ' . $association['name'],
            'association' => $association,
        ]);
        Session::clearFormState();
    }

    public function update(Request $request, array $params): void
    {
        $id = (int) $params['id'];
        $association = (new Association())->find($id);
        if ($association === null) {
            Response::notFound();
        }

        $input = [
            'subscription_start' => (string) $request->input('subscription_start', ''),
            'subscription_end'   => (string) $request->input('subscription_end', ''),
            'is_active'          => $request->input('is_active') ? 1 : 0,
        ];
        $validator = Validator::make($input, [
            'subscription_start' => 'date',
            'subscription_end'   => 'date',
        ]);
        if ($validator->fails()) {
            $this->withErrors($validator->errors(), $input);
        }

        if ($input['subscription_start'] && $input['subscription_end']
            && strtotime($input['subscription_end']) < strtotime($input['subscription_start'])) {
            $this->withErrors(['subscription_end' => 'End date must be after the start date.'], $input);
        }

        (new Association())->updateSubscription(
            $id,
            $input['subscription_start'] ?: null,
            $input['subscription_end'] ?: null,
            (bool) $input['is_active']
        );

        $this->flash('success', 'Subscription updated.');
        $this->redirect('/admin/associations');
    }
}
