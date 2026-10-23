<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesInvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'invoice_type' => $this->invoice_type,
            'invoice_date' => $this->invoice_date->format('Y-m-d'),
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'phone' => $this->customer->phone,
                'current_balance' => (float)$this->customer->current_balance,
            ]),
            'patient_name' => $this->patient_name,
            'doctor_name' => $this->doctor_name,
            'subtotal' => (float)$this->subtotal,
            'item_discount_amount' => (float)$this->item_discount_amount,
            'bill_discount_amount' => (float)$this->bill_discount_amount,
            'taxable_amount' => (float)$this->taxable_amount,
            'cgst_amount' => (float)$this->cgst_amount,
            'sgst_amount' => (float)$this->sgst_amount,
            'igst_amount' => (float)$this->igst_amount,
            'total_tax' => (float)$this->total_tax,
            'round_off' => (float)$this->round_off,
            'net_amount' => (float)$this->net_amount,
            'paid_amount' => (float)$this->paid_amount,
            'due_amount' => (float)$this->due_amount,
            'payment_status' => $this->payment_status,
            'status' => $this->status,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name,
                'dosage_form' => $item->product?->dosage_form,
                'batch_number' => $item->batch_number,
                'expiry_date' => $item->expiry_date->format('Y-m-d'),
                'quantity' => (float)$item->quantity,
                'free_quantity' => (float)$item->free_quantity,
                'mrp' => (float)$item->mrp,
                'unit_price' => (float)$item->unit_price,
                'discount_percent' => (float)$item->discount_percent,
                'discount_amount' => (float)$item->discount_amount,
                'taxable_value' => (float)$item->taxable_value,
                'gst_rate' => (float)$item->gst_rate,
                'total_amount' => (float)$item->total_amount,
            ])),
            'payments' => $this->whenLoaded('payments', fn () => $this->payments->map(fn ($p) => [
                'payment_number' => $p->payment_number,
                'amount' => (float)$p->amount,
                'payment_mode' => $p->payment_mode,
                'transaction_reference' => $p->transaction_reference,
            ])),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
