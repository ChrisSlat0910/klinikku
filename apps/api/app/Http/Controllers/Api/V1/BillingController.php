<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Billing\ProcessPaymentRequest;
use App\Models\Invoice;
use App\Models\QueueItem;
use App\Models\User;
use App\Services\BillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingController extends BaseController
{
    public function __construct(private BillingService $billingService) {}

    public function pending(Request $request): JsonResponse
    {
        $clinicId = (int) app('clinic_id');

        $queueItems = QueueItem::where('clinic_id', $clinicId)
            ->whereDate('queue_date', now()->toDateString())
            ->whereIn('status', ['done'])
            ->whereDoesntHave('invoice', function ($q): void {
                $q->where('status', 'paid');
            })
            ->with(['patient', 'doctor'])
            ->get();

        return $this->success($queueItems);
    }

    public function show(int $queueItemId): JsonResponse
    {
        $clinicId = (int) app('clinic_id');

        $queueItem = QueueItem::where('clinic_id', $clinicId)->find($queueItemId);

        if (! $queueItem instanceof QueueItem) {
            return $this->notFound('Queue item not found.');
        }

        $invoice = $this->billingService->getOrCreateInvoice($queueItem);

        return $this->success($invoice);
    }

    public function pay(ProcessPaymentRequest $request, int $queueItemId): JsonResponse
    {
        $clinicId = (int) app('clinic_id');
        $user = $request->user();

        if (! $user instanceof User) {
            return $this->error('UNAUTHENTICATED', 'Not authenticated.', 401);
        }

        $queueItem = QueueItem::where('clinic_id', $clinicId)->find($queueItemId);

        if (! $queueItem instanceof QueueItem) {
            return $this->notFound('Queue item not found.');
        }

        $invoice = Invoice::where('queue_item_id', $queueItemId)
            ->where('clinic_id', $clinicId)
            ->first();

        if (! $invoice instanceof Invoice) {
            $invoice = $this->billingService->getOrCreateInvoice($queueItem);
        }

        $validated = $request->validated();

        try {
            $invoice = $this->billingService->processPayment(
                $invoice,
                $user,
                $validated['payment_method'],
                (float) ($validated['discount'] ?? 0)
            );

            return $this->success($invoice);
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'ALREADY_PAID') {
                return $this->error('ALREADY_PAID', 'This invoice has already been paid.', 409);
            }

            throw $e;
        }
    }

    public function history(Request $request): JsonResponse
    {
        $clinicId = (int) app('clinic_id');

        $query = Invoice::where('clinic_id', $clinicId)
            ->where('status', 'paid')
            ->with(['patient', 'items']);

        if ($request->has('date')) {
            $query->whereDate('paid_at', $request->date);
        }

        $invoices = $query->orderByDesc('paid_at')->paginate(20);

        return $this->paginated($invoices->items(), [
            'page' => $invoices->currentPage(),
            'per_page' => $invoices->perPage(),
            'total' => $invoices->total(),
            'last_page' => $invoices->lastPage(),
        ]);
    }
}
