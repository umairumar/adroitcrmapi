<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Services\Auth\AuthorizationService;
use App\Services\Finance\BankReconciliationService;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function __construct(
        private readonly AuthorizationService $authz,
        private readonly BankReconciliationService $bank,
    ) {}

    public function index()
    {
        return response()->json([
            'status' => true,
            'data' => BankAccount::with('glAccount')->where('is_active', true)->get(),
        ]);
    }

    public function store(Request $request)
    {
        if (! $this->authz->hasPermission($request->user(), 'finance.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $account = BankAccount::create($request->only([
            'gl_account_id', 'name', 'bank_name', 'account_number', 'currency', 'opening_balance',
        ]));

        return response()->json(['status' => true, 'data' => $account], 201);
    }

    public function transactions(int $id)
    {
        return response()->json([
            'status' => true,
            'data' => BankTransaction::where('bank_account_id', $id)->orderByDesc('transaction_date')->paginate(50),
        ]);
    }

    public function importCsv(Request $request, int $id)
    {
        if (! $this->authz->hasPermission($request->user(), 'finance.manage')) {
            return response()->json(['status' => false, 'message' => 'Forbidden'], 403);
        }

        $request->validate(['rows' => 'required|array']);

        $account = BankAccount::findOrFail($id);
        $count = $this->bank->importCsv($account, $request->rows);

        return response()->json(['status' => true, 'message' => "Imported {$count} transactions"]);
    }

    public function reconcileSuggestions(int $id)
    {
        $account = BankAccount::findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => $this->bank->suggestMatches($account),
        ]);
    }

    public function reconcile(Request $request, int $txnId)
    {
        $txn = BankTransaction::findOrFail($txnId);
        $this->bank->reconcile($txn, $request->crm_payment_id);

        return response()->json(['status' => true, 'data' => $txn->fresh()]);
    }
}
