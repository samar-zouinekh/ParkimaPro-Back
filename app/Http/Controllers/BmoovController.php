<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BmoovController extends Controller
{
    public static function saveTransaction($data, $reference)
    {
        app('db')->insert('insert into transactions (reference, parking_id, product, provider, status, paid_amount,original_amount, tariff_class, currency, created_at, updated_at, promotion_id, payment_type) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            $reference,
            $data['parking'],
            $data['product'],
            $data['provider'],
            'paid',
            $data['amount'],
            $data['original_amount'],
            $data['tariff_class'],
            $data['currency'],
            date('Y-m-d H:i:s'),
            date('Y-m-d H:i:s'),
            $data['promotion_id'],
            $data['payment_type'],
        ]);
    }
  /**
     * Update a transaction by it's reference.
     *
     * @param string $reference The transaction reference
     * @param string $status The new transaction status
     *
     * @return int
     */
    public static function updateTransaction(string $reference, string $status,$shiftId = null)
    {
        if ($status === 'verified' || $status === 'booked') {  
            // Get transaction ID
            $result = app('db')->select(
                'select id
                 from transactions
                 where reference = ? limit 1',
                [$reference]);

            $invoiceNumber = $reference .'-' .$shiftId;

            app('db')->update('update transactions set status = \'' . $status . '\',receipt_number = \'' . $invoiceNumber . '\', updated_at = \'' . date('Y-m-d H:i:s') . '\' where reference = ?', [$reference]);

        } else {
            app('db')->update('update transactions set status = \'' . $status . '\', updated_at = \'' . date('Y-m-d H:i:s') . '\' where reference = ?', [$reference]);
        }
    }


}
