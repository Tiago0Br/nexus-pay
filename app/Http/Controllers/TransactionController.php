<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionProduct;
use App\Services\PaymentOrchestrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class TransactionController extends Controller
{
    protected PaymentOrchestrator $orchestrator;

    public function __construct(PaymentOrchestrator $orchestrator)
    {
        $this->orchestrator = $orchestrator;
    }

    public function index(): JsonResponse
    {
        return response()
            ->json(Transaction::all());
    }

    public function show(int $id): JsonResponse
    {
        return response()
            ->json(Transaction::with(['client', 'products'])->findOrFail($id));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_name' => ['required', 'string', 'max:255'],
            'client_email' => ['required', 'email'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'cardNumber' => ['required', 'string', 'size:16'],
            'cvv' => ['required', 'string', 'min:3', 'max:4'],
        ]);

        $product = Product::query()->findOrFail($validated['product_id']);
        $totalAmount = $product->amount * $validated['quantity'];

        $client = Client::query()->firstOrCreate(
            ['email' => $validated['client_email']],
            ['name' => $validated['client_name']]
        );

        $paymentData = [
            'amount' => $totalAmount,
            'client_name' => $client->name,
            'client_email' => $client->email,
            'card_number' => $validated['cardNumber'],
            'cvv' => $validated['cvv']
        ];

        DB::beginTransaction();

        try {
            $paymentResult = $this->orchestrator->processPayment($paymentData);

            $transaction = Transaction::query()->create([
                'client_id' => $client->id,
                'gateway_id' => $paymentResult['gateway_id'],
                'external_id' => $paymentResult['external_id'],
                'status' => 'SUCCESS',
                'amount' => $totalAmount,
                'card_last_numbers' => substr(string: $validated['cardNumber'], offset: -4)
            ]);

            TransactionProduct::query()->create([
                'transaction_id' => $transaction->id,
                'product_id' => $product->id,
                'quantity' => $validated['quantity']
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Pagamento aprovado com sucesso!',
                'transaction_id' => $transaction->id,
                'gateway_used' => $paymentResult['gateway_id'],
                'status' => 'SUCCESS'
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(data: [
                'message' => 'Pagamento recusado em todos os gateways disponíveis.',
                'error' => $e->getMessage()
            ], status: 402);
        }
    }

    public function chargeBack(Transaction $transaction): JsonResponse
    {
        if ($transaction->status === 'CHARGED_BACK') {
            return response()->json(data: [
                'message' => 'Esta transação já foi reembolsada anteriormente.'
            ], status: 400);
        }

        try {
            $this->orchestrator->refundPayment($transaction);

            $transaction->update(['status' => 'CHARGED_BACK']);

            return response()->json([
                'message' => 'Reembolso realizado com sucesso!',
                'transaction_id' => $transaction->id,
                'status' => 'CHARGED_BACK'
            ]);

        } catch (Exception $e) {
            return response()->json(data: [
                'message' => 'Falha ao processar o reembolso.',
                'error' => $e->getMessage()
            ], status: 422);
        }
    }
}
