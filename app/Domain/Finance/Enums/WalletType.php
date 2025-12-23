<?php

namespace App\Domain\Finance\Enums;

enum WalletType: string
{
    // Merchant
    case MERCHANT_TOTAL = 'merchant_total';
    case MERCHANT_AVAILABLE = 'merchant_available_for_payout';

    // Courier
    case COURIER_SHIPMENTS_COD = 'courier_shipment_cod_in_hand';
    case COURIER_COMMISSION = 'courier_commission_balance';

    // Branch
    case BRANCH_COMMISSION = 'branch_commission_balance';
    case BRANCH_SHIPMENTS_TOTAL = 'branch_shipments_total_balance';
    case BRANCH_TREASURY = 'branch_treasury_balance';
    case BRANCH_COURIERS_CUSTODY = 'branch_couriers_custody_balance';
}
