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

final class BankAccountController extends Controller
{
    public function index(Request $request): void
    {
        $assocId = Auth::associationId();
        $accounts = (new BankAccount())->allForAssociation($assocId, 'account_name ASC');
        $model = new BankAccount();
        foreach ($accounts as &$a) {
            $ledger = $model->ledger((int) $a['id'], $assocId);
            $a['balance'] = $ledger['balance'];
        }
        unset($a);

        $this->view('bank_accounts.index', [
            'title'    => 'Bank Accounts',
            'accounts' => $accounts,
        ]);
        Session::clearFormState();
    }

    public function create(Request $request): void
    {
        $this->view('bank_accounts.form', ['title' => 'Add Bank Account', 'account' => null]);
        Session::clearFormState();
    }

    public function store(Request $request): void
    {
        $data = $this->validated($request);
        $data['association_id'] = Auth::associationId();
        (new BankAccount())->create($data);
        $this->flash('success', 'Bank account added.');
        $this->redirect('/bank-accounts');
    }

    public function show(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $account = (new BankAccount())->findForAssociation((int) $params['id'], $assocId);
        if ($account === null) {
            Response::notFound();
        }
        $ledger = (new BankAccount())->ledger((int) $account['id'], $assocId);
        $this->view('bank_accounts.show', [
            'title'   => $account['account_name'],
            'account' => $account,
            'ledger'  => $ledger,
        ]);
    }

    public function edit(Request $request, array $params): void
    {
        $account = (new BankAccount())->findForAssociation((int) $params['id'], Auth::associationId());
        if ($account === null) {
            Response::notFound();
        }
        $this->view('bank_accounts.form', ['title' => 'Edit Bank Account', 'account' => $account]);
        Session::clearFormState();
    }

    public function update(Request $request, array $params): void
    {
        $assocId = Auth::associationId();
        $account = (new BankAccount())->findForAssociation((int) $params['id'], $assocId);
        if ($account === null) {
            Response::notFound();
        }
        (new BankAccount())->update((int) $account['id'], $this->validated($request));
        $this->flash('success', 'Bank account updated.');
        $this->redirect('/bank-accounts');
    }

    /** @return array<string,mixed> */
    private function validated(Request $request): array
    {
        $input = [
            'account_name'          => (string) $request->input('account_name', ''),
            'type'                  => (string) $request->input('type', 'association'),
            'account_number_masked' => (string) $request->input('account_number_masked', ''),
            'opening_balance'       => (string) $request->input('opening_balance', '0'),
            'description'           => (string) $request->input('description', ''),
            'is_active'             => $request->input('is_active') ? 1 : 0,
        ];
        $validator = Validator::make($input, [
            'account_name'    => 'required|min:2|max:160',
            'type'            => 'required|in:association,treasurer',
            'opening_balance' => 'decimal',
            'account_number_masked' => 'max:40',
            'description'     => 'max:255',
        ]);
        if ($validator->fails()) {
            $this->withErrors($validator->errors(), $input);
        }
        $input['opening_balance'] = $input['opening_balance'] !== '' ? $input['opening_balance'] : '0';
        $input['account_number_masked'] = $input['account_number_masked'] ?: null;
        $input['description'] = $input['description'] ?: null;
        return $input;
    }
}
