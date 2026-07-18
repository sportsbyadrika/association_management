<?php

declare(strict_types=1);

/**
 * Route definitions. $router is provided by app/bootstrap.php.
 *
 * @var \App\Core\Router $router
 */

use App\Controllers\AssociationController;
use App\Controllers\AuthController;
use App\Controllers\BankAccountController;
use App\Controllers\DashboardController;
use App\Controllers\DemandController;
use App\Controllers\DemandPurposeController;
use App\Controllers\ExpenditureController;
use App\Controllers\FinancialYearController;
use App\Controllers\HomeController;
use App\Controllers\MasterController;
use App\Controllers\MemberController;
use App\Controllers\MemberSelfController;
use App\Controllers\PhotoController;
use App\Controllers\ProfileController;
use App\Controllers\ProjectController;
use App\Controllers\ReceiptController;
use App\Controllers\ReportController;
use App\Controllers\SubscriptionController;

// ---- Public --------------------------------------------------------------
$router->get('/', [HomeController::class, 'index']);

$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout'], ['auth' => true]);

$router->get('/password/forgot', [AuthController::class, 'showForgot']);
$router->post('/password/forgot', [AuthController::class, 'sendReset']);
$router->get('/password/reset', [AuthController::class, 'showReset']);
$router->post('/password/reset', [AuthController::class, 'doReset']);

// Forced first-login password change (auth required).
$router->get('/password/force-change', [AuthController::class, 'showForceChange'], ['auth' => true]);
$router->post('/password/force-change', [AuthController::class, 'doForceChange'], ['auth' => true]);

// ---- Authenticated shared ------------------------------------------------
$router->group(['auth' => true], function ($router): void {
    $router->get('/dashboard', [DashboardController::class, 'index'], [
        'roles' => ['association_admin', 'association_staff'],
    ]);

    // Profile + self password change (all roles).
    $router->get('/profile', [ProfileController::class, 'show']);
    $router->post('/profile', [ProfileController::class, 'update']);
    $router->get('/profile/password', [ProfileController::class, 'showPassword']);
    $router->post('/profile/password', [ProfileController::class, 'updatePassword']);

    // Gated photo serving (authorization checked in controller).
    $router->get('/photo/{type}/{id}', [PhotoController::class, 'show']);
});

// ---- Super Admin ---------------------------------------------------------
$router->group(['auth' => true, 'roles' => ['super_admin']], function ($router): void {
    $router->get('/admin/associations', [AssociationController::class, 'index']);
    $router->get('/admin/associations/create', [AssociationController::class, 'create']);
    $router->post('/admin/associations', [AssociationController::class, 'store']);
    $router->get('/admin/associations/{id}/edit', [AssociationController::class, 'edit']);
    $router->post('/admin/associations/{id}', [AssociationController::class, 'update']);

    $router->get('/admin/associations/{id}/subscription', [SubscriptionController::class, 'edit']);
    $router->post('/admin/associations/{id}/subscription', [SubscriptionController::class, 'update']);

    $router->get('/admin/admins', [AssociationController::class, 'admins']);
    $router->get('/admin/associations/{id}/admins/create', [AssociationController::class, 'createAdmin']);
    $router->post('/admin/associations/{id}/admins', [AssociationController::class, 'storeAdmin']);
    $router->get('/admin/admins/{id}/edit', [AssociationController::class, 'editAdmin']);
    $router->post('/admin/admins/{id}', [AssociationController::class, 'updateAdmin']);
    $router->post('/admin/admins/{id}/reset-password', [AssociationController::class, 'resetAdminPassword']);
});

// ---- Association Admin + Staff (operational) -----------------------------
$router->group(['auth' => true, 'roles' => ['association_admin', 'association_staff']], function ($router): void {
    // Members
    $router->get('/members', [MemberController::class, 'index']);
    $router->get('/members/create', [MemberController::class, 'create']);
    // Bulk upload wizard (association_admin only — enforced in the controller).
    // Registered before /members/{id} so "bulk" is not treated as an id.
    $router->get('/members/bulk', [MemberController::class, 'bulkForm']);
    $router->post('/members/bulk/parse', [MemberController::class, 'bulkParse']);
    $router->get('/members/bulk/preview', [MemberController::class, 'bulkPreview']);
    $router->post('/members/bulk/import', [MemberController::class, 'bulkImport']);
    $router->post('/members', [MemberController::class, 'store']);
    $router->get('/members/{id}', [MemberController::class, 'show']);
    $router->get('/members/{id}/edit', [MemberController::class, 'edit']);
    $router->post('/members/{id}', [MemberController::class, 'update']);
    $router->post('/members/{id}/delete', [MemberController::class, 'destroy']);
    $router->get('/members/{id}/ledger', [MemberController::class, 'ledger']);

    // Demands (bulk raise: details + member selection -> confirm -> create)
    $router->get('/demands', [DemandController::class, 'index']);
    $router->get('/demands/create', [DemandController::class, 'create']);
    $router->post('/demands/preview', [DemandController::class, 'preview']);
    $router->post('/demands/bulk', [DemandController::class, 'bulkStore']);
    $router->post('/demands/{id}/mark-paid', [DemandController::class, 'markPaid']);
    $router->post('/demands/{id}/reopen', [DemandController::class, 'reopen']);
    $router->post('/demands/{id}/delete', [DemandController::class, 'destroy']);

    // Receipts
    $router->get('/receipts', [ReceiptController::class, 'index']);
    $router->get('/receipts/create', [ReceiptController::class, 'create']);
    $router->post('/receipts', [ReceiptController::class, 'store']);
    $router->post('/receipts/{id}/delete', [ReceiptController::class, 'destroy']);

    // Expenditure
    $router->get('/expenditures', [ExpenditureController::class, 'index']);
    $router->get('/expenditures/create', [ExpenditureController::class, 'create']);
    $router->post('/expenditures', [ExpenditureController::class, 'store']);
    $router->get('/expenditures/{id}/edit', [ExpenditureController::class, 'edit']);
    $router->post('/expenditures/{id}', [ExpenditureController::class, 'update']);
    $router->post('/expenditures/{id}/delete', [ExpenditureController::class, 'destroy']);

    // Projects
    $router->get('/projects', [ProjectController::class, 'index']);
    $router->get('/projects/create', [ProjectController::class, 'create']);
    $router->post('/projects', [ProjectController::class, 'store']);
    $router->get('/projects/{id}', [ProjectController::class, 'show']);
    $router->get('/projects/{id}/ledger', [ProjectController::class, 'ledger']);
    $router->get('/projects/{id}/edit', [ProjectController::class, 'edit']);
    $router->post('/projects/{id}', [ProjectController::class, 'update']);
    $router->post('/projects/{id}/milestones', [ProjectController::class, 'storeMilestone']);
});

// ---- Association Admin only (masters, bank accounts, reports) -------------
$router->group(['auth' => true, 'roles' => ['association_admin']], function ($router): void {
    // Financial years master (registered before the generic {master} routes
    // so "financial-years" is not captured as a generic master key).
    $router->get('/masters/financial-years', [FinancialYearController::class, 'index']);
    $router->get('/masters/financial-years/create', [FinancialYearController::class, 'create']);
    $router->post('/masters/financial-years', [FinancialYearController::class, 'store']);
    $router->get('/masters/financial-years/{id}/edit', [FinancialYearController::class, 'edit']);
    $router->post('/masters/financial-years/{id}', [FinancialYearController::class, 'update']);
    $router->post('/masters/financial-years/{id}/toggle', [FinancialYearController::class, 'toggle']);

    // Demand purposes master (mandatory/optional type). Registered before the
    // generic {master} routes.
    $router->get('/masters/demand-purposes', [DemandPurposeController::class, 'index']);
    $router->get('/masters/demand-purposes/create', [DemandPurposeController::class, 'create']);
    $router->post('/masters/demand-purposes', [DemandPurposeController::class, 'store']);
    $router->get('/masters/demand-purposes/{id}/edit', [DemandPurposeController::class, 'edit']);
    $router->post('/masters/demand-purposes/{id}', [DemandPurposeController::class, 'update']);
    $router->post('/masters/demand-purposes/{id}/toggle', [DemandPurposeController::class, 'toggle']);

    // Masters — one generic controller keyed by {master} segment.
    $router->get('/masters/{master}', [MasterController::class, 'index']);
    $router->get('/masters/{master}/create', [MasterController::class, 'create']);
    $router->post('/masters/{master}', [MasterController::class, 'store']);
    $router->get('/masters/{master}/{id}/edit', [MasterController::class, 'edit']);
    $router->post('/masters/{master}/{id}', [MasterController::class, 'update']);
    $router->post('/masters/{master}/{id}/toggle', [MasterController::class, 'toggle']);

    // Bank accounts + ledger
    $router->get('/bank-accounts', [BankAccountController::class, 'index']);
    $router->get('/bank-accounts/create', [BankAccountController::class, 'create']);
    $router->post('/bank-accounts', [BankAccountController::class, 'store']);
    $router->get('/bank-accounts/{id}', [BankAccountController::class, 'show']);
    $router->get('/bank-accounts/{id}/edit', [BankAccountController::class, 'edit']);
    $router->post('/bank-accounts/{id}', [BankAccountController::class, 'update']);
});

// ---- Reports (admin + staff view; export) --------------------------------
$router->group(['auth' => true, 'roles' => ['association_admin', 'association_staff']], function ($router): void {
    $router->get('/reports', [ReportController::class, 'index']);
    $router->get('/reports/members', [ReportController::class, 'members']);
    $router->get('/reports/member-ledger', [ReportController::class, 'memberLedger']);
    $router->get('/reports/income', [ReportController::class, 'income']);
    $router->get('/reports/expenditure', [ReportController::class, 'expenditure']);
    $router->get('/reports/purpose-ledger', [ReportController::class, 'purposeLedger']);
});

// ---- Member self-service -------------------------------------------------
$router->group(['auth' => true, 'roles' => ['member']], function ($router): void {
    $router->get('/member/profile', [MemberSelfController::class, 'profile']);
    $router->get('/member/ledger', [MemberSelfController::class, 'ledger']);
});
