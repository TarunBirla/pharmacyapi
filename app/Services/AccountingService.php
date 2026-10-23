<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Branch;
use App\Models\FinancialYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class AccountingService
{
    /**
     * Provision default pharmacy Chart of Accounts for an organization.
     */
    public function seedDefaultChartOfAccounts(Organization $organization): void
    {
        $defaultAccounts = [
            // Assets (1000 - 1999)
            ['account_code' => '1010', 'account_name' => 'Cash in Hand', 'account_type' => 'asset', 'subtype' => 'cash', 'is_system' => true],
            ['account_code' => '1020', 'account_name' => 'Bank Account', 'account_type' => 'asset', 'subtype' => 'bank', 'is_system' => true],
            ['account_code' => '1030', 'account_name' => 'Accounts Receivable (Customers)', 'account_type' => 'asset', 'subtype' => 'accounts_receivable', 'is_system' => true],
            ['account_code' => '1040', 'account_name' => 'Inventory Asset (Stock in Trade)', 'account_type' => 'asset', 'subtype' => 'inventory', 'is_system' => true],
            ['account_code' => '1050', 'account_name' => 'Input CGST (ITC)', 'account_type' => 'asset', 'subtype' => 'tax_input_credit', 'is_system' => true],
            ['account_code' => '1051', 'account_name' => 'Input SGST (ITC)', 'account_type' => 'asset', 'subtype' => 'tax_input_credit', 'is_system' => true],
            ['account_code' => '1052', 'account_name' => 'Input IGST (ITC)', 'account_type' => 'asset', 'subtype' => 'tax_input_credit', 'is_system' => true],

            // Liabilities (2000 - 2999)
            ['account_code' => '2010', 'account_name' => 'Accounts Payable (Suppliers)', 'account_type' => 'liability', 'subtype' => 'accounts_payable', 'is_system' => true],
            ['account_code' => '2020', 'account_name' => 'Output CGST Payable', 'account_type' => 'liability', 'subtype' => 'tax_payable', 'is_system' => true],
            ['account_code' => '2021', 'account_name' => 'Output SGST Payable', 'account_type' => 'liability', 'subtype' => 'tax_payable', 'is_system' => true],
            ['account_code' => '2022', 'account_name' => 'Output IGST Payable', 'account_type' => 'liability', 'subtype' => 'tax_payable', 'is_system' => true],

            // Equity (3000 - 3999)
            ['account_code' => '3010', 'account_name' => 'Owner Capital & Retained Earnings', 'account_type' => 'equity', 'subtype' => 'equity', 'is_system' => true],

            // Revenue (4000 - 4999)
            ['account_code' => '4010', 'account_name' => 'Sales Revenue (Medicines)', 'account_type' => 'revenue', 'subtype' => 'operating_revenue', 'is_system' => true],
            ['account_code' => '4020', 'account_name' => 'Sales Returns & Allowances', 'account_type' => 'revenue', 'subtype' => 'contra_revenue', 'is_system' => true],
            ['account_code' => '4030', 'account_name' => 'Customer Discounts Allowed', 'account_type' => 'revenue', 'subtype' => 'contra_revenue', 'is_system' => true],

            // Expenses (5000 - 5999)
            ['account_code' => '5010', 'account_name' => 'Cost of Goods Sold (COGS)', 'account_type' => 'expense', 'subtype' => 'cogs', 'is_system' => true],
            ['account_code' => '5020', 'account_name' => 'Supplier Discounts Received', 'account_type' => 'expense', 'subtype' => 'contra_expense', 'is_system' => true],
            ['account_code' => '5030', 'account_name' => 'Damaged & Expired Medicine Loss', 'account_type' => 'expense', 'subtype' => 'operating_expense', 'is_system' => true],
            ['account_code' => '5040', 'account_name' => 'Invoice Round-Off Difference', 'account_type' => 'expense', 'subtype' => 'operating_expense', 'is_system' => true],
        ];

        foreach ($defaultAccounts as $acc) {
            Account::firstOrCreate(
                [
                    'organization_id' => $organization->id,
                    'account_code' => $acc['account_code']
                ],
                $acc
            );
        }
    }

    /**
     * Get or create active financial year for an organization.
     */
    public function getOrCreateFinancialYear(int $organizationId, string $date): FinancialYear
    {
        $yearDate = date('Y-m-d', strtotime($date));
        $fy = FinancialYear::where('organization_id', $organizationId)
            ->where('start_date', '<=', $yearDate)
            ->where('end_date', '>=', $yearDate)
            ->first();

        if (!$fy) {
            $year = (int)date('Y', strtotime($date));
            $month = (int)date('n', strtotime($date));
            // In India, FY runs from April 1 to March 31
            if ($month >= 4) {
                $start = "$year-04-01";
                $end = ($year + 1) . "-03-31";
                $name = "FY $year-" . ($year + 1);
            } else {
                $start = ($year - 1) . "-04-01";
                $end = "$year-03-31";
                $name = "FY " . ($year - 1) . "-$year";
            }

            $fy = FinancialYear::create([
                'organization_id' => $organizationId,
                'name' => $name,
                'start_date' => $start,
                'end_date' => $end,
                'is_closed' => false,
            ]);
        }

        return $fy;
    }

    /**
     * Find system account by code, auto-seeding if missing.
     */
    public function getAccount(int $organizationId, string $code): Account
    {
        $acc = Account::where('organization_id', $organizationId)
            ->where('account_code', $code)
            ->first();

        if (!$acc) {
            $org = Organization::find($organizationId);
            if ($org) {
                $this->seedDefaultChartOfAccounts($org);
            }
            $acc = Account::where('organization_id', $organizationId)
                ->where('account_code', $code)
                ->firstOrFail();
        }

        return $acc;
    }

    /**
     * Automatically create double-entry journal for a posted sales invoice.
     */
    public function postSalesInvoiceJournal(SalesInvoice $invoice, User $user): JournalEntry
    {
        return DB::transaction(function () use ($invoice, $user) {
            $orgId = $invoice->organization_id;
            $branchId = $invoice->branch_id;
            $fy = $this->getOrCreateFinancialYear($orgId, $invoice->invoice_date->format('Y-m-d'));

            $cashAcc = $this->getAccount($orgId, '1010');
            $bankAcc = $this->getAccount($orgId, '1020');
            $arAcc = $this->getAccount($orgId, '1030');
            $salesRevAcc = $this->getAccount($orgId, '4010');
            $cgstAcc = $this->getAccount($orgId, '2020');
            $sgstAcc = $this->getAccount($orgId, '2021');
            $igstAcc = $this->getAccount($orgId, '2022');
            $roundOffAcc = $this->getAccount($orgId, '5040');

            $lines = [];

            // Debits: Payments received by mode
            $paidTotal = 0;
            foreach ($invoice->payments as $payment) {
                $amt = (float)$payment->amount;
                if ($amt <= 0) continue;
                $paidTotal += $amt;

                $targetAcc = match ($payment->payment_mode) {
                    'cash' => $cashAcc,
                    'upi', 'card', 'bank_transfer', 'cheque' => $bankAcc,
                    default => $cashAcc,
                };

                $lines[] = [
                    'account_id' => $targetAcc->id,
                    'type' => 'debit',
                    'amount' => $amt,
                    'narration' => "Payment received via {$payment->payment_mode} for {$invoice->invoice_number}",
                ];
            }

            // Debit: Customer Accounts Receivable for remaining due amount
            $dueAmt = (float)$invoice->due_amount;
            if ($dueAmt > 0) {
                $lines[] = [
                    'account_id' => $arAcc->id,
                    'type' => 'debit',
                    'amount' => $dueAmt,
                    'narration' => "Credit sale balance for {$invoice->invoice_number}",
                ];
            }

            // Credits: Taxable sales revenue
            $taxable = (float)$invoice->taxable_amount;
            if ($taxable > 0) {
                $lines[] = [
                    'account_id' => $salesRevAcc->id,
                    'type' => 'credit',
                    'amount' => $taxable,
                    'narration' => "Medicine sales revenue {$invoice->invoice_number}",
                ];
            }

            // Credits: Taxes
            if ((float)$invoice->cgst_amount > 0) {
                $lines[] = [
                    'account_id' => $cgstAcc->id,
                    'type' => 'credit',
                    'amount' => (float)$invoice->cgst_amount,
                    'narration' => "Output CGST on {$invoice->invoice_number}",
                ];
            }
            if ((float)$invoice->sgst_amount > 0) {
                $lines[] = [
                    'account_id' => $sgstAcc->id,
                    'type' => 'credit',
                    'amount' => (float)$invoice->sgst_amount,
                    'narration' => "Output SGST on {$invoice->invoice_number}",
                ];
            }
            if ((float)$invoice->igst_amount > 0) {
                $lines[] = [
                    'account_id' => $igstAcc->id,
                    'type' => 'credit',
                    'amount' => (float)$invoice->igst_amount,
                    'narration' => "Output IGST on {$invoice->invoice_number}",
                ];
            }

            // Round off
            $roundOff = (float)$invoice->round_off;
            if ($roundOff != 0) {
                if ($roundOff > 0) {
                    // positive round off adds to total net -> credit round-off
                    $lines[] = [
                        'account_id' => $roundOffAcc->id,
                        'type' => 'credit',
                        'amount' => abs($roundOff),
                        'narration' => "Round off gain on {$invoice->invoice_number}",
                    ];
                } else {
                    // negative round off reduces total net -> debit round-off expense
                    $lines[] = [
                        'account_id' => $roundOffAcc->id,
                        'type' => 'debit',
                        'amount' => abs($roundOff),
                        'narration' => "Round off discount on {$invoice->invoice_number}",
                    ];
                }
            }

            return $this->createJournal(
                organizationId: $orgId,
                branchId: $branchId,
                financialYearId: $fy->id,
                entryDate: $invoice->invoice_date->format('Y-m-d'),
                sourceType: 'sales_invoice',
                sourceId: $invoice->id,
                referenceNumber: $invoice->invoice_number,
                narration: "Sales Invoice {$invoice->invoice_number} posted",
                lines: $lines,
                userId: $user->id
            );
        });
    }

    /**
     * Automatically create double-entry journal for a sales return.
     */
    public function postSalesReturnJournal(SalesReturn $return, User $user): JournalEntry
    {
        return DB::transaction(function () use ($return, $user) {
            $orgId = $return->organization_id;
            $branchId = $return->branch_id;
            $fy = $this->getOrCreateFinancialYear($orgId, $return->return_date->format('Y-m-d'));

            $salesReturnAcc = $this->getAccount($orgId, '4020');
            $cgstAcc = $this->getAccount($orgId, '2020');
            $sgstAcc = $this->getAccount($orgId, '2021');
            $igstAcc = $this->getAccount($orgId, '2022');
            $arAcc = $this->getAccount($orgId, '1030');
            $cashAcc = $this->getAccount($orgId, '1010');

            $lines = [];

            // Debits: Reversal of sales revenue and output taxes
            $lines[] = [
                'account_id' => $salesReturnAcc->id,
                'type' => 'debit',
                'amount' => (float)$return->subtotal,
                'narration' => "Sales Return {$return->credit_note_number} taxable reversal",
            ];

            $taxAmt = (float)$return->tax_amount;
            if ($taxAmt > 0) {
                $cgst = round($taxAmt / 2, 2);
                $sgst = round($taxAmt - $cgst, 2);

                $lines[] = [
                    'account_id' => $cgstAcc->id,
                    'type' => 'debit',
                    'amount' => $cgst,
                    'narration' => "Output CGST reversal for Return {$return->credit_note_number}",
                ];
                $lines[] = [
                    'account_id' => $sgstAcc->id,
                    'type' => 'debit',
                    'amount' => $sgst,
                    'narration' => "Output SGST reversal for Return {$return->credit_note_number}",
                ];
            }

            // Credits: Customer AR reduced or Cash Refund given
            $creditAcc = match ($return->refund_mode) {
                'cash', 'cash_refund' => $cashAcc,
                default => $arAcc,
            };

            $lines[] = [
                'account_id' => $creditAcc->id,
                'type' => 'credit',
                'amount' => (float)$return->total_amount,
                'narration' => "Credit note / refund for {$return->credit_note_number}",
            ];

            return $this->createJournal(
                organizationId: $orgId,
                branchId: $branchId,
                financialYearId: $fy->id,
                entryDate: $return->return_date->format('Y-m-d'),
                sourceType: 'sales_return',
                sourceId: $return->id,
                referenceNumber: $return->credit_note_number,
                narration: "Sales Return {$return->credit_note_number} processed",
                lines: $lines,
                userId: $user->id
            );
        });
    }

    /**
     * Automatically create double-entry journal for a posted purchase invoice.
     */
    public function postPurchaseInvoiceJournal(PurchaseInvoice $invoice, User $user): JournalEntry
    {
        return DB::transaction(function () use ($invoice, $user) {
            $orgId = $invoice->organization_id;
            $branchId = $invoice->branch_id;
            $fy = $this->getOrCreateFinancialYear($orgId, $invoice->invoice_date->format('Y-m-d'));

            $inventoryAcc = $this->getAccount($orgId, '1040');
            $cgstItcAcc = $this->getAccount($orgId, '1050');
            $sgstItcAcc = $this->getAccount($orgId, '1051');
            $igstItcAcc = $this->getAccount($orgId, '1052');
            $apAcc = $this->getAccount($orgId, '2010');
            $roundOffAcc = $this->getAccount($orgId, '5040');

            $lines = [];

            // Debits: Inventory Asset (taxable purchases)
            $taxable = (float)$invoice->taxable_amount;
            if ($taxable > 0) {
                $lines[] = [
                    'account_id' => $inventoryAcc->id,
                    'type' => 'debit',
                    'amount' => $taxable,
                    'narration' => "Inventory addition from Purchase Invoice {$invoice->invoice_number}",
                ];
            }

            // Debits: Input Tax Credit (ITC)
            if ((float)$invoice->cgst_amount > 0) {
                $lines[] = [
                    'account_id' => $cgstItcAcc->id,
                    'type' => 'debit',
                    'amount' => (float)$invoice->cgst_amount,
                    'narration' => "Input CGST ITC on Purchase {$invoice->invoice_number}",
                ];
            }
            if ((float)$invoice->sgst_amount > 0) {
                $lines[] = [
                    'account_id' => $sgstItcAcc->id,
                    'type' => 'debit',
                    'amount' => (float)$invoice->sgst_amount,
                    'narration' => "Input SGST ITC on Purchase {$invoice->invoice_number}",
                ];
            }
            if ((float)$invoice->igst_amount > 0) {
                $lines[] = [
                    'account_id' => $igstItcAcc->id,
                    'type' => 'debit',
                    'amount' => (float)$invoice->igst_amount,
                    'narration' => "Input IGST ITC on Purchase {$invoice->invoice_number}",
                ];
            }

            // Debits: Additional Charges (Freight / Logistics)
            $charges = (float)$invoice->additional_charges;
            if ($charges > 0) {
                $freightAcc = Account::where('organization_id', $orgId)->where('account_code', '5050')->first();
                if (!$freightAcc) {
                    $freightAcc = Account::create([
                        'organization_id' => $orgId,
                        'account_code' => '5050',
                        'account_name' => 'Inward Freight & Delivery Charges',
                        'account_type' => 'expense',
                        'subtype' => 'operating_expense',
                        'is_system' => true,
                        'is_active' => true,
                    ]);
                }

                $lines[] = [
                    'account_id' => $freightAcc->id,
                    'type' => 'debit',
                    'amount' => $charges,
                    'narration' => "Inward Freight & Logistics on Purchase {$invoice->invoice_number}",
                ];
            }

            // Credits: Accounts Payable (Supplier)
            $net = (float)$invoice->net_amount;
            $lines[] = [
                'account_id' => $apAcc->id,
                'type' => 'credit',
                'amount' => $net,
                'narration' => "Payable to supplier for {$invoice->invoice_number}",
            ];

            // Round off adjustment
            $roundOff = (float)$invoice->round_off;
            if ($roundOff != 0) {
                if ($roundOff > 0) {
                    $lines[] = [
                        'account_id' => $roundOffAcc->id,
                        'type' => 'debit',
                        'amount' => abs($roundOff),
                        'narration' => "Purchase round-off expense {$invoice->invoice_number}",
                    ];
                } else {
                    $lines[] = [
                        'account_id' => $roundOffAcc->id,
                        'type' => 'credit',
                        'amount' => abs($roundOff),
                        'narration' => "Purchase round-off discount {$invoice->invoice_number}",
                    ];
                }
            }

            return $this->createJournal(
                organizationId: $orgId,
                branchId: $branchId,
                financialYearId: $fy->id,
                entryDate: $invoice->invoice_date->format('Y-m-d'),
                sourceType: 'purchase_invoice',
                sourceId: $invoice->id,
                referenceNumber: $invoice->invoice_number,
                narration: "Purchase Invoice {$invoice->invoice_number} from {$invoice->supplier->name}",
                lines: $lines,
                userId: $user->id
            );
        });
    }

    /**
     * Automatically create double-entry journal for a purchase return (Debit Note).
     */
    public function postPurchaseReturnJournal(PurchaseReturn $return, User $user): JournalEntry
    {
        return DB::transaction(function () use ($return, $user) {
            $orgId = $return->organization_id;
            $branchId = $return->branch_id;
            $fy = $this->getOrCreateFinancialYear($orgId, $return->return_date->format('Y-m-d'));

            $inventoryAcc = $this->getAccount($orgId, '1040');
            $cgstItcAcc = $this->getAccount($orgId, '1050');
            $sgstItcAcc = $this->getAccount($orgId, '1051');
            $igstItcAcc = $this->getAccount($orgId, '1052');
            $apAcc = $this->getAccount($orgId, '2010');

            $lines = [];

            // Debits: Accounts Payable reduction (Debit note against supplier)
            $lines[] = [
                'account_id' => $apAcc->id,
                'type' => 'debit',
                'amount' => (float)$return->total_amount,
                'narration' => "Debit note {$return->debit_note_number} against supplier",
            ];

            // Credits: Inventory Asset deduction
            $lines[] = [
                'account_id' => $inventoryAcc->id,
                'type' => 'credit',
                'amount' => (float)$return->subtotal,
                'narration' => "Stock deduction for Purchase Return {$return->debit_note_number}",
            ];

            // Credits: Reversal of Input Tax Credit
            $taxAmt = (float)$return->tax_amount;
            if ($taxAmt > 0) {
                $cgst = round($taxAmt / 2, 2);
                $sgst = round($taxAmt - $cgst, 2);

                $lines[] = [
                    'account_id' => $cgstItcAcc->id,
                    'type' => 'credit',
                    'amount' => $cgst,
                    'narration' => "Reversal of Input CGST on Return {$return->debit_note_number}",
                ];
                $lines[] = [
                    'account_id' => $sgstItcAcc->id,
                    'type' => 'credit',
                    'amount' => $sgst,
                    'narration' => "Reversal of Input SGST on Return {$return->debit_note_number}",
                ];
            }

            return $this->createJournal(
                organizationId: $orgId,
                branchId: $branchId,
                financialYearId: $fy->id,
                entryDate: $return->return_date->format('Y-m-d'),
                sourceType: 'purchase_return',
                sourceId: $return->id,
                referenceNumber: $return->debit_note_number,
                narration: "Debit Note {$return->debit_note_number} for Purchase Return",
                lines: $lines,
                userId: $user->id
            );
        });
    }

    /**
     * Record supplier payment journal entry.
     */
    public function postSupplierPaymentJournal(array $paymentData, User $user): JournalEntry
    {
        return DB::transaction(function () use ($paymentData, $user) {
            $orgId = $user->organization_id;
            $branchId = $user->branch_id;
            $entryDate = $paymentData['payment_date'] ?? date('Y-m-d');
            $fy = $this->getOrCreateFinancialYear($orgId, $entryDate);

            $apAcc = $this->getAccount($orgId, '2010');
            $cashAcc = $this->getAccount($orgId, '1010');
            $bankAcc = $this->getAccount($orgId, '1020');

            $amount = (float)$paymentData['amount'];
            $mode = $paymentData['payment_mode'] ?? 'bank_transfer';
            $creditAcc = match ($mode) {
                'cash' => $cashAcc,
                default => $bankAcc,
            };

            $lines = [
                [
                    'account_id' => $apAcc->id,
                    'type' => 'debit',
                    'amount' => $amount,
                    'narration' => "Payment made to supplier. Ref: " . ($paymentData['reference_number'] ?? 'N/A'),
                ],
                [
                    'account_id' => $creditAcc->id,
                    'type' => 'credit',
                    'amount' => $amount,
                    'narration' => "Disbursement via $mode",
                ]
            ];

            return $this->createJournal(
                organizationId: $orgId,
                branchId: $branchId,
                financialYearId: $fy->id,
                entryDate: $entryDate,
                sourceType: 'payment',
                sourceId: null,
                referenceNumber: $paymentData['reference_number'] ?? null,
                narration: "Supplier payment recorded",
                lines: $lines,
                userId: $user->id
            );
        });
    }

    /**
     * Core balanced double-entry journal poster.
     */
    public function createJournal(
        int $organizationId,
        int $branchId,
        int $financialYearId,
        string $entryDate,
        ?string $sourceType,
        ?int $sourceId,
        ?string $referenceNumber,
        ?string $narration,
        array $lines,
        ?int $userId
    ): JournalEntry {
        $totalDebit = 0.00;
        $totalCredit = 0.00;

        foreach ($lines as $line) {
            $amt = round((float)$line['amount'], 2);
            if ($line['type'] === 'debit') {
                $totalDebit += $amt;
            } elseif ($line['type'] === 'credit') {
                $totalCredit += $amt;
            } else {
                throw new InvalidArgumentException("Invalid line type {$line['type']}");
            }
        }

        $totalDebit = round($totalDebit, 2);
        $totalCredit = round($totalCredit, 2);

        // Verify balance
        if (abs($totalDebit - $totalCredit) > 0.05) {
            throw new RuntimeException("Unbalanced journal entry: Total Debit (₹{$totalDebit}) != Total Credit (₹{$totalCredit})");
        }

        // Generate entry sequence
        $entryNumber = sprintf('JRN-%s-%06d', date('Ymd'), JournalEntry::withoutGlobalScopes()->where('organization_id', $organizationId)->count() + 1);

        /** @var JournalEntry $journal */
        $journal = JournalEntry::create([
            'organization_id' => $organizationId,
            'branch_id' => $branchId,
            'financial_year_id' => $financialYearId,
            'entry_number' => $entryNumber,
            'entry_date' => $entryDate,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'reference_number' => $referenceNumber,
            'narration' => $narration,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'is_posted' => true,
            'created_by' => $userId,
        ]);

        foreach ($lines as $line) {
            $amt = round((float)$line['amount'], 2);
            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'account_id' => $line['account_id'],
                'type' => $line['type'],
                'amount' => $amt,
                'narration' => $line['narration'] ?? null,
            ]);

            // Mutate running account balance
            /** @var Account $acc */
            $acc = Account::findOrFail($line['account_id']);
            if (in_array($acc->account_type, ['asset', 'expense'])) {
                // Debit increases, Credit decreases
                $delta = ($line['type'] === 'debit') ? $amt : -$amt;
            } else {
                // Liability, Equity, Revenue: Credit increases, Debit decreases
                $delta = ($line['type'] === 'credit') ? $amt : -$amt;
            }
            $acc->increment('current_balance', $delta);
        }

        return $journal->load('lines.account');
    }

    /**
     * Generate Trial Balance.
     */
    public function getTrialBalance(int $organizationId, ?int $branchId = null, ?string $asOfDate = null): array
    {
        $query = Account::where('organization_id', $organizationId);
        if ($branchId) {
            $query->where(function ($q) use ($branchId) {
                $q->whereNull('branch_id')->orWhere('branch_id', $branchId);
            });
        }

        $accounts = $query->orderBy('account_code')->get();

        $rows = [];
        $totalDebit = 0.00;
        $totalCredit = 0.00;

        foreach ($accounts as $acc) {
            $bal = (float)$acc->current_balance;
            $debit = 0.00;
            $credit = 0.00;

            if (in_array($acc->account_type, ['asset', 'expense'])) {
                if ($bal >= 0) {
                    $debit = $bal;
                } else {
                    $credit = abs($bal);
                }
            } else {
                if ($bal >= 0) {
                    $credit = $bal;
                } else {
                    $debit = abs($bal);
                }
            }

            if ($debit > 0 || $credit > 0) {
                $rows[] = [
                    'account_id' => $acc->id,
                    'account_code' => $acc->account_code,
                    'account_name' => $acc->account_name,
                    'account_type' => $acc->account_type,
                    'subtype' => $acc->subtype,
                    'debit' => $debit,
                    'credit' => $credit,
                ];
                $totalDebit += $debit;
                $totalCredit += $credit;
            }
        }

        return [
            'as_of_date' => $asOfDate ?: date('Y-m-d'),
            'accounts' => $rows,
            'total_debit' => round($totalDebit, 2),
            'total_credit' => round($totalCredit, 2),
            'is_balanced' => abs(round($totalDebit, 2) - round($totalCredit, 2)) < 0.05,
        ];
    }

    /**
     * Generate Profit and Loss (Income Statement).
     */
    public function getProfitAndLoss(int $organizationId, ?int $branchId = null, string $fromDate, string $toDate): array
    {
        $accounts = Account::where('organization_id', $organizationId)
            ->whereIn('account_type', ['revenue', 'expense'])
            ->get();

        $operatingRevenues = [];
        $costOfGoodsSold = [];
        $operatingExpenses = [];

        $totalRevenue = 0.00;
        $totalCogs = 0.00;
        $totalExpenses = 0.00;

        foreach ($accounts as $acc) {
            $bal = (float)$acc->current_balance;
            if ($acc->account_type === 'revenue') {
                $operatingRevenues[] = [
                    'account_code' => $acc->account_code,
                    'account_name' => $acc->account_name,
                    'amount' => $bal,
                ];
                $totalRevenue += $bal;
            } elseif ($acc->subtype === 'cogs') {
                $costOfGoodsSold[] = [
                    'account_code' => $acc->account_code,
                    'account_name' => $acc->account_name,
                    'amount' => $bal,
                ];
                $totalCogs += $bal;
            } else {
                $operatingExpenses[] = [
                    'account_code' => $acc->account_code,
                    'account_name' => $acc->account_name,
                    'amount' => $bal,
                ];
                $totalExpenses += $bal;
            }
        }

        $grossProfit = round($totalRevenue - $totalCogs, 2);
        $netProfit = round($grossProfit - $totalExpenses, 2);

        return [
            'period' => [
                'from_date' => $fromDate,
                'to_date' => $toDate,
            ],
            'revenues' => [
                'items' => $operatingRevenues,
                'total' => round($totalRevenue, 2),
            ],
            'cost_of_goods_sold' => [
                'items' => $costOfGoodsSold,
                'total' => round($totalCogs, 2),
            ],
            'gross_profit' => $grossProfit,
            'expenses' => [
                'items' => $operatingExpenses,
                'total' => round($totalExpenses, 2),
            ],
            'net_profit' => $netProfit,
        ];
    }

    /**
     * Generate Balance Sheet.
     */
    public function getBalanceSheet(int $organizationId, ?int $branchId = null, string $asOfDate): array
    {
        $accounts = Account::where('organization_id', $organizationId)->get();

        $assets = [];
        $liabilities = [];
        $equity = [];

        $totalAssets = 0.00;
        $totalLiabilities = 0.00;
        $totalEquity = 0.00;

        foreach ($accounts as $acc) {
            $bal = (float)$acc->current_balance;
            if ($acc->account_type === 'asset') {
                $assets[] = [
                    'account_code' => $acc->account_code,
                    'account_name' => $acc->account_name,
                    'amount' => $bal,
                ];
                $totalAssets += $bal;
            } elseif ($acc->account_type === 'liability') {
                $liabilities[] = [
                    'account_code' => $acc->account_code,
                    'account_name' => $acc->account_name,
                    'amount' => $bal,
                ];
                $totalLiabilities += $bal;
            } elseif ($acc->account_type === 'equity') {
                $equity[] = [
                    'account_code' => $acc->account_code,
                    'account_name' => $acc->account_name,
                    'amount' => $bal,
                ];
                $totalEquity += $bal;
            }
        }

        return [
            'as_of_date' => $asOfDate,
            'assets' => [
                'items' => $assets,
                'total' => round($totalAssets, 2),
            ],
            'liabilities' => [
                'items' => $liabilities,
                'total' => round($totalLiabilities, 2),
            ],
            'equity' => [
                'items' => $equity,
                'total' => round($totalEquity, 2),
            ],
            'total_liabilities_and_equity' => round($totalLiabilities + $totalEquity, 2),
        ];
    }
}
