<?php

namespace App\Domain\Finance\Enums;

enum LedgerEntryType: string
{
    case DEBIT = 'debit';
    case CREDIT = 'credit';
}
