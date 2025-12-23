<?php

namespace App\Application\Finance;

use App\Domain\Finance\Enums\LedgerEntryType;
use App\Domain\Finance\Models\LedgerEntry;
use App\Domain\Finance\Models\LedgerTransaction;
use App\Domain\Finance\Models\Wallet;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class LedgerService
{
    /**
     * @param array<int,array{wallet: Wallet, type: LedgerEntryType, amount: int, memo?: string|null}> $entries
     */
    public function postTransaction(
        string $title,
        array $entries,
        ?string $idempotencyKey = null,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?string $description = null,
    ): LedgerTransaction {
        if (count($entries) < 2) {
            throw new InvalidArgumentException('Ledger transaction must have at least 2 entries.');
        }

        $this->assertBalanced($entries);

        return DB::transaction(function () use ($title, $entries, $idempotencyKey, $sourceType, $sourceId, $description) {

            if ($idempotencyKey) {
                $existing = LedgerTransaction::query()->where('idempotency_key', $idempotencyKey)->first();
                if ($existing) {
                    return $existing; // idempotent safe return
                }
            }

            $tx = LedgerTransaction::create([
                'idempotency_key' => $idempotencyKey,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'title' => $title,
                'description' => $description,
                'posted_at' => now(),
            ]);

            foreach ($entries as $e) {
                LedgerEntry::create([
                    'ledger_transaction_id' => $tx->id,
                    'wallet_id' => $e['wallet']->id,
                    'type' => $e['type']->value,
                    'amount' => $e['amount'],
                    'memo' => $e['memo'] ?? null,
                ]);

                // تحديث cached_balance بشكل محكوم (اختياري)
                $delta = $e['type'] === LedgerEntryType::DEBIT ? $e['amount'] : -$e['amount'];
                $e['wallet']->increment('cached_balance', $delta);
            }

            return $tx;
        });
    }

    /**
     * @param array<int,array{wallet: Wallet, type: LedgerEntryType, amount: int}> $entries
     */
    public function assertBalanced(array $entries): void
    {
        $debits = 0;
        $credits = 0;

        foreach ($entries as $e) {
            if ($e['amount'] <= 0) {
                throw new InvalidArgumentException('Ledger entry amount must be > 0.');
            }

            if ($e['type'] === LedgerEntryType::DEBIT) {
                $debits += $e['amount'];
            } elseif ($e['type'] === LedgerEntryType::CREDIT) {
                $credits += $e['amount'];
            } else {
                throw new InvalidArgumentException('Invalid ledger entry type.');
            }
        }

        if ($debits !== $credits) {
            throw new RuntimeException("Ledger not balanced. Debits={$debits}, Credits={$credits}");
        }
    }
}
