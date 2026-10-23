<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $invoice->invoice_number }} - Print</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Courier New', Courier, monospace; }
        body { width: 80mm; margin: auto; padding: 5mm; color: #000; font-size: 11px; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .right { text-align: right; }
        .line { border-bottom: 1px dashed #000; margin: 4px 0; }
        table { width: 100%; border-collapse: collapse; margin: 4px 0; }
        th, td { font-size: 10px; padding: 2px 0; }
        th { text-align: left; }
        @media print {
            body { width: 100%; padding: 0; }
            @page { margin: 0; size: 80mm auto; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="center">
        <div class="bold" style="font-size: 15px;">{{ $invoice->branch->organization->name ?? 'APEX PHARMA' }}</div>
        <div>{{ $invoice->branch->name }}</div>
        <div>{{ $invoice->branch->address ?? 'Main Road, Medical Market' }}</div>
        <div class="bold">GSTIN: {{ $invoice->branch->gstin ?? '27AAPCA1234F1Z5' }}</div>
        <div>DL No: 20B/21B-MH-102938</div>
        <div>Phone: +91 98765 43210</div>
    </div>

    <div class="line"></div>

    <div>
        <div><span class="bold">Tax Invoice:</span> {{ $invoice->invoice_number }}</div>
        <div><span class="bold">Date:</span> {{ $invoice->invoice_date }} {{ $invoice->created_at->format('H:i') }}</div>
        <div><span class="bold">Customer:</span> {{ $invoice->customer->name ?? 'Walk-in Cash Customer' }}</div>
        @if($invoice->doctor)
            <div><span class="bold">Doctor:</span> {{ $invoice->doctor->name }} (Reg: {{ $invoice->doctor->registration_number }})</div>
        @endif
        @if($invoice->prescription_number)
            <div><span class="bold">Rx No:</span> {{ $invoice->prescription_number }}</div>
        @endif
    </div>

    <div class="line"></div>

    <table>
        <thead>
            <tr>
                <th style="width: 45%;">Item / Batch</th>
                <th class="center" style="width: 15%;">Qty</th>
                <th class="right" style="width: 20%;">MRP</th>
                <th class="right" style="width: 20%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
            <tr>
                <td colspan="4">
                    <span class="bold">{{ $item->product->name }}</span>
                    <span style="font-size: 9px;"> ({{ $item->batch->batch_number ?? 'B1' }} Exp: {{ $item->batch?->expiry_date ? date('m/y', strtotime($item->batch->expiry_date)) : '' }})</span>
                </td>
            </tr>
            <tr>
                <td style="font-size: 9px; padding-left: 5px;">HSN: {{ $item->product->hsn_code }}</td>
                <td class="center">{{ $item->quantity }}</td>
                <td class="right">₹{{ number_format($item->unit_price, 2) }}</td>
                <td class="right bold">₹{{ number_format($item->total_amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="line"></div>

    <table>
        <tr>
            <td>Total Items:</td>
            <td class="right bold">{{ $invoice->items->count() }} ({{ $invoice->items->sum('quantity') }} units)</td>
        </tr>
        <tr>
            <td>Taxable Amount:</td>
            <td class="right">₹{{ number_format($invoice->taxable_amount, 2) }}</td>
        </tr>
        <tr>
            <td>CGST (Output):</td>
            <td class="right">₹{{ number_format($invoice->cgst_amount, 2) }}</td>
        </tr>
        <tr>
            <td>SGST (Output):</td>
            <td class="right">₹{{ number_format($invoice->sgst_amount, 2) }}</td>
        </tr>
        @if($invoice->discount_amount > 0)
        <tr>
            <td>Discount:</td>
            <td class="right">-₹{{ number_format($invoice->discount_amount, 2) }}</td>
        </tr>
        @endif
        <tr style="font-size: 13px;">
            <td class="bold">NET PAYABLE:</td>
            <td class="right bold">₹{{ number_format($invoice->net_amount, 2) }}</td>
        </tr>
    </table>

    <div class="line"></div>

    <div>
        <div class="bold">Payment Mode(s):</div>
        @foreach($invoice->payments as $p)
            <div style="display: flex; justify-content: space-between; font-size: 10px;">
                <span>{{ strtoupper($p->payment_method) }}:</span>
                <span>₹{{ number_format($p->amount, 2) }}</span>
            </div>
        @endforeach
    </div>

    <div class="line"></div>

    <div class="center" style="font-size: 9px; margin-top: 5px;">
        <div>Thank you! Get well soon.</div>
        <div>Medicines without batch/bill cannot be returned.</div>
        <div>Schedule H drugs dispensed against registered Rx only.</div>
        <div style="margin-top: 5px;">*** Powered by Apex Pharma ERP ***</div>
    </div>

</body>
</html>
