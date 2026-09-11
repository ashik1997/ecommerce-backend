<?php

namespace App\Http\Controllers\Account\Models;

use App\Http\Controllers\Account\Models\AcAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcTransaction extends Model
{
    use HasFactory;
    protected $guarded = []; 

    protected $table = "ac_transactions";

    protected static function booted()
    {
        static::created(function (AcTransaction $transaction) {
            $transaction->applyAccountBalanceImpact(1);
        });

        static::updated(function (AcTransaction $transaction) {
            $transaction->applyAccountBalanceImpact(-1, $transaction->getOriginal());
            $transaction->applyAccountBalanceImpact(1);
        });

        static::deleted(function (AcTransaction $transaction) {
            $transaction->applyAccountBalanceImpact(-1);
        });
    }

    // Relationship for debit account
    public function debitAccount() {
        return $this->belongsTo(AcAccount::class, 'debit_account_id');
    }

    // Relationship for credit account
    public function creditAccount() {
        return $this->belongsTo(AcAccount::class, 'credit_account_id');
    }

    public function user() {
        return $this->belongsTo(User::class, 'creator');
    }

    private function applyAccountBalanceImpact(int $direction = 1, ?array $values = null): void
    {
        $values = $values ?: $this->getAttributes();

        if (($values['status'] ?? 'active') !== 'active') {
            return;
        }

        $debitAccountId = $values['debit_account_id'] ?? null;
        $creditAccountId = $values['credit_account_id'] ?? null;
        $debitAmount = (float) ($values['debit_amt'] ?? 0);
        $creditAmount = (float) ($values['credit_amt'] ?? 0);

        if ($debitAccountId && $debitAmount > 0) {
            $this->adjustAccountBalance((int) $debitAccountId, $debitAmount, 'debit', $direction);
        }

        if ($creditAccountId && $creditAmount > 0) {
            $this->adjustAccountBalance((int) $creditAccountId, $creditAmount, 'credit', $direction);
        }
    }

    private function adjustAccountBalance(int $accountId, float $amount, string $entrySide, int $direction): void
    {
        $account = AcAccount::find($accountId);

        if (!$account) {
            return;
        }

        $normalBalance = $account->normal_balance ?: $this->normalBalanceFromType($account->account_type);
        $signedAmount = $amount;

        if ($normalBalance && $normalBalance !== $entrySide) {
            $signedAmount *= -1;
        }

        AcAccount::whereKey($accountId)->increment('balance', $signedAmount * $direction);
    }

    private function normalBalanceFromType(?string $accountType): ?string
    {
        if (in_array($accountType, ['asset', 'expense'], true)) {
            return 'debit';
        }

        if (in_array($accountType, ['liability', 'equity', 'revenue'], true)) {
            return 'credit';
        }

        return null;
    }

}
