<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Billing\Services\InvoiceService;
use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Models\InvoiceItem;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreInvoiceItemRequest;
use App\Http\Requests\Api\V1\UpdateInvoiceItemRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceItemController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
    ) {}

    /**
     * Get all items for a specific invoice
     */
    public function index(Invoice $invoice, Request $request): JsonResponse
    {
        // Eager load untuk prevent N+1 queries
        $items = $invoice->items()
            ->when($request->query('item_type'), fn ($q, $type) => $q->where('item_type', $type))
            ->orderBy('id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Invoice items loaded successfully.',
            'data' => [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'invoice_status' => $invoice->invoice_status,
                'items' => $items->map(fn (InvoiceItem $item): array => [
                    'id' => $item->id,
                    'item_type' => $item->item_type,
                    'item_code' => $item->item_code,
                    'item_name' => $item->item_name,
                    'description' => $item->description,
                    'unit_price' => (float) $item->unit_price,
                    'quantity' => (float) $item->quantity,
                    'discount_amount' => (float) $item->discount_amount,
                    'tax_amount' => (float) $item->tax_amount,
                    'line_total' => (float) $item->line_total,
                    'item_date' => $item->item_date?->format('Y-m-d'),
                    'notes' => $item->notes,
                    'created_at' => $item->created_at?->format('Y-m-d H:i:s'),
                    'updated_at' => $item->updated_at?->format('Y-m-d H:i:s'),
                ])->values(),
                'summary' => [
                    'total_items' => $items->count(),
                    'subtotal' => (float) $items->sum(fn ($item) => $item->getSubtotalAttribute()),
                    'tax_total' => (float) $items->sum('tax_amount'),
                    'discount_total' => (float) $items->sum('discount_amount'),
                    'grand_total' => (float) $items->sum('line_total'),
                ],
            ],
            'meta' => [],
        ]);
    }

    /**
     * Store a new invoice item
     */
    public function store(StoreInvoiceItemRequest $request, Invoice $invoice): JsonResponse
    {
        try {
            $item = $this->invoiceService->addItem(
                $invoice,
                $request->validated(),
                $request->user(),
            );

            return response()->json([
                'success' => true,
                'message' => 'Invoice item berhasil ditambahkan.',
                'data' => [
                    'item' => [
                        'id' => $item->id,
                        'item_type' => $item->item_type,
                        'item_name' => $item->item_name,
                        'unit_price' => (float) $item->unit_price,
                        'quantity' => (float) $item->quantity,
                        'discount_amount' => (float) $item->discount_amount,
                        'tax_amount' => (float) $item->tax_amount,
                        'line_total' => (float) $item->line_total,
                    ],
                    'invoice' => [
                        'id' => $invoice->id,
                        'invoice_status' => $invoice->invoice_status,
                        'subtotal_amount' => (float) $invoice->subtotal_amount,
                        'tax_amount' => (float) $invoice->tax_amount,
                        'discount_amount' => (float) $invoice->discount_amount,
                        'grand_total' => (float) $invoice->grand_total,
                        'remaining_amount' => (float) $invoice->remaining_amount,
                    ],
                ],
                'meta' => [],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add invoice item.',
                'errors' => [
                    'general' => $e->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * Get specific invoice item
     */
    public function show(Invoice $invoice, InvoiceItem $item): JsonResponse
    {
        if ($item->invoice_id !== $invoice->id) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice item tidak ditemukan di invoice ini.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Invoice item loaded successfully.',
            'data' => [
                'id' => $item->id,
                'invoice_id' => $invoice->id,
                'item_type' => $item->item_type,
                'item_code' => $item->item_code,
                'item_name' => $item->item_name,
                'description' => $item->description,
                'unit_price' => (float) $item->unit_price,
                'quantity' => (float) $item->quantity,
                'discount_amount' => (float) $item->discount_amount,
                'tax_amount' => (float) $item->tax_amount,
                'line_total' => (float) $item->line_total,
                'item_date' => $item->item_date?->format('Y-m-d'),
                'notes' => $item->notes,
                'created_at' => $item->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $item->updated_at?->format('Y-m-d H:i:s'),
            ],
            'meta' => [],
        ]);
    }

    /**
     * Update invoice item
     */
    public function update(UpdateInvoiceItemRequest $request, Invoice $invoice, InvoiceItem $item): JsonResponse
    {
        if ($item->invoice_id !== $invoice->id) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice item tidak ditemukan di invoice ini.',
            ], 404);
        }

        try {
            $updatedItem = $this->invoiceService->updateItem(
                $item,
                $request->validated(),
                $request->user(),
            );

            return response()->json([
                'success' => true,
                'message' => 'Invoice item berhasil diupdate.',
                'data' => [
                    'item' => [
                        'id' => $updatedItem->id,
                        'item_name' => $updatedItem->item_name,
                        'unit_price' => (float) $updatedItem->unit_price,
                        'quantity' => (float) $updatedItem->quantity,
                        'line_total' => (float) $updatedItem->line_total,
                    ],
                    'invoice' => [
                        'id' => $invoice->id,
                        'subtotal_amount' => (float) $invoice->subtotal_amount,
                        'tax_amount' => (float) $invoice->tax_amount,
                        'grand_total' => (float) $invoice->grand_total,
                        'remaining_amount' => (float) $invoice->remaining_amount,
                    ],
                ],
                'meta' => [],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update invoice item.',
                'errors' => [
                    'general' => $e->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * Delete invoice item
     */
    public function destroy(Invoice $invoice, InvoiceItem $item): JsonResponse
    {
        if ($item->invoice_id !== $invoice->id) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice item tidak ditemukan di invoice ini.',
            ], 404);
        }

        try {
            $this->invoiceService->deleteItem($item, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Invoice item berhasil dihapus.',
                'data' => [
                    'invoice' => [
                        'id' => $invoice->id,
                        'subtotal_amount' => (float) $invoice->subtotal_amount,
                        'tax_amount' => (float) $invoice->tax_amount,
                        'grand_total' => (float) $invoice->grand_total,
                        'remaining_amount' => (float) $invoice->remaining_amount,
                    ],
                ],
                'meta' => [],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete invoice item.',
                'errors' => [
                    'general' => $e->getMessage(),
                ],
            ], 500);
        }
    }
}