<?php

declare(strict_types=1);

/**
 * Order lifecycle labels. See lang/fr/order.php for the rationale.
 *
 * Arabic agrees with "التسجيل" (masculine), so the statuses are phrased to match
 * it rather than being transliterated from the French participles.
 */
return [
    'status.awaiting_payment' => 'بانتظار الدفع',
    'status.cancelled'        => 'ملغاة',
    'status.expired'          => 'منتهية الصلاحية',
    'status.failed'           => 'فشل الدفع',
    'status.paid'             => 'مدفوعة',
    'status.partially_refunded' => 'مستردة جزئياً',
    'status.pending'          => 'قيد الانتظار',
    'status.refunded'         => 'مستردة',

    'reference'   => 'المرجع',
    'placed_on'   => 'تاريخ التسجيل',
    'amount'      => 'المبلغ',
    'no_orders'   => 'لا توجد تسجيلات بعد.',
    'pay_now'     => 'الدفع الآن',
    'download_invoice' => 'تحميل الفاتورة',
];
