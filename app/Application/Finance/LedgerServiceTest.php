<?php

namespace Tests\Feature\Finance;

use App\Application\Finance\LedgerService;
use App\Application\Finance\WalletService;
use App\Domain\Finance\Enums\LedgerEntryType;
use App\Domain\Finance\Enums\WalletType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Branch;

class LedgerServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_posts_balanced_transaction_and_updates_cached_balance(): void
    {
        $branch = Branch::factory()->create();

        $wallets = app(WalletService::class);
        $treasury = $wallets->getOrCreate($branch, WalletType::BRANCH_TREASURY);
        $custody  = $wallets->getOrCreate($branch, WalletType::BRANCH_COURIERS_CUSTODY);

        $ledger = app(LedgerService::class);

        $tx = $ledger->postTransaction(
            title: 'Test Move',
            entries: [
                ['wallet' => $treasury, 'type' => LedgerEntryType::DEBIT, 'amount' => 100],
                ['wallet' => $custody,  'type' => LedgerEntryType::CREDIT, 'amount' => 100],
            ],
            idempotencyKey: 'test-1'
        );

        $this->assertNotNull($tx->id);

        $this->assertEquals(100, $treasury->fresh()->cached_balance);
        $this->assertEquals(-100, $custody->fresh()->cached_balance);
    }

    public function test_it_is_idempotent_by_key(): void
    {
        $branch = Branch::factory()->create();
        $wallets = app(WalletService::class);
        $w1 = $wallets->getOrCreate($branch, WalletType::BRANCH_TREASURY);
        $w2 = $wallets->getOrCreate($branch, WalletType::BRANCH_COURIERS_CUSTODY);

        $ledger = app(LedgerService::class);

        $tx1 = $ledger->postTransaction('X', [
            ['wallet' => $w1, 'type' => LedgerEntryType::DEBIT, 'amount' => 50],
            ['wallet' => $w2, 'type' => LedgerEntryType::CREDIT, 'amount' => 50],
        ], 'same-key');

        $tx2 = $ledger->postTransaction('X', [
            ['wallet' => $w1, 'type' => LedgerEntryType::DEBIT, 'amount' => 50],
            ['wallet' => $w2, 'type' => LedgerEntryType::CREDIT, 'amount' => 50],
        ], 'same-key');

        $this->assertEquals($tx1->id, $tx2->id);
    }
}
