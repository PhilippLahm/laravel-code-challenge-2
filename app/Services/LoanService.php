<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\ScheduledRepayment;
use App\Models\User;
use Carbon\Carbon;

class LoanService
{
    /**
     * Create a Loan
     *
     * @param User $user
     * @param int $amount
     * @param string $currencyCode
     * @param int $terms
     * @param string $processedAt
     *
     * @return Loan
     */
    public function createLoan(User $user, int $amount, string $currencyCode, int $terms, string $processedAt): Loan
    {
        $loan = Loan::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'terms' => $terms,
            'outstanding_amount' => $amount,
            'currency_code' => $currencyCode,
            'processed_at' => $processedAt,
        ]);
        $scheduledPayments = [];
        for ($i = 1; $i <= $terms; $i++) {
            $scheduledPayments[] = [
                'amount' => $this->calculateAmountByTerms($i, $terms, $amount),
                'outstanding_amount' => $this->calculateAmountByTerms($i, $terms, $amount),
                'currency_code' => $currencyCode,
                'due_date' => Carbon::parse('2020-01-20')->addMonthsNoOverflow($i)->toDateString(),
                'status' => ScheduledRepayment::STATUS_DUE,
            ];
        }
        $loan->scheduledRepayments()->createMany($scheduledPayments);

        return $loan;
    }

    /**
     * Repay Scheduled Repayments for a Loan
     *
     * @param Loan $loan
     * @param int $amount
     * @param string $currencyCode
     * @param string $receivedAt
     *
     * @return Loan
     */
    public function repayLoan(Loan $loan, int $amount, string $currencyCode, string $receivedAt): Loan
    {
        $outStandingAmount = $loan->amount - $amount;
        $scheduledRepayments = $loan->scheduledRepayments()
            ->where('status', ScheduledRepayment::STATUS_DUE)
            ->orderBy('due_date')
            ->get();

        $receivedAmount = 0;
        foreach ($scheduledRepayments as $scheduledRepayment) {
            if ($amount > $scheduledRepayment->amount) {
                $repaymentOutStandingAmount = 0; // full repayment
            }

            if ($scheduledRepayment->due_date === $receivedAt) {
                $scheduledRepayment->update([
                    'outstanding_amount' => $repaymentOutStandingAmount ?? $amount,
                    'status' => ScheduledRepayment::STATUS_REPAID,
                ]);
                $receivedAmount += $scheduledRepayment->amount;
                $amount -= $scheduledRepayment->amount;
            } elseif ($scheduledRepayment->due_date !== $receivedAt) {
                if ($amount > 0)
                    $scheduledRepayment->update([
                        'outstanding_amount' => $amount,
                        'status' => ScheduledRepayment::STATUS_PARTIAL,
                    ]);
                $receivedAmount += $amount;
                $amount -= $amount;
            }
        }

        foreach ($loan->scheduledRepayments as $scheduledRepayment) {
            if ($scheduledRepayment->status !== ScheduledRepayment::STATUS_REPAID) {
                $loan->status = Loan::STATUS_DUE;
            }
        }

        if ($loan->status == Loan::STATUS_REPAID) {
            $loan->outstanding_amount = 0;
        } else {
            $loan->outstanding_amount = $outStandingAmount;
        }

        $loan->receivedRepeyments()->create([
            'amount' => $receivedAmount,
            'currency_code' => $currencyCode,
            'received_at' => $receivedAt,
        ]);
        $loan->save();

        return $loan;
    }

    private function calculateAmountByTerms($index, int $terms, $amount)
    {
        if ($index === $terms) {
            $amount = round($amount / $terms, 0, PHP_ROUND_HALF_UP);
        } else {
            $amount = floor($amount / 3);
        }
        return $amount;
    }
}
