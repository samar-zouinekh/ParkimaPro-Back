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
    public static function saveEnforcement($data)
    {
        app('db')->insert('insert into enforcements (parking_id, agent_shift, agent_name, enforcement_date, type, gravity, cause, amount, payment_status, payment_methode, enforcement_reference, enforced_license_plate, enforced_phone_number, enforced_car_picture) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
        //  dd($data),
            $data['parking'],
            $data['agent_shift'],
            $data['agent_name'],
            date('Y-m-d H:i:s'),
            $data['type'],
            $data['gravity'],
            $data['cause'],
            $data['amount'],
            'pending',
            '',
            $data['enforcement_reference'],
            $data['enforced_license_plate'],
            $data['enforced_phone_number'],
            $data['enforced_car_picture'],
        ]);
    }
    public static function updateEnforcement(string $enforcement_reference, string $status,$shiftId = null, $payment_methode)
    {
        if ($status === 'verified' || $status === 'booked') {  
            // Get transaction ID
            $result = app('db')->select(
                'select id
                 from transactions
                 where reference = ? limit 1',
                [$enforcement_reference]);

            $invoiceNumber = $enforcement_reference .'-' .$shiftId;

            $payment_methode= $payment_methode;

            app('db')->update('update enforcements set status = \'' . $status . '\',receipt_number = \'' . $invoiceNumber . '\', updated_at = \'' . date('Y-m-d H:i:s') . '\' where reference = ?', [$enforcement_reference]);

        } else {
            app('db')->update('update enforcements set status = \'' . $status . '\', updated_at = \'' . date('Y-m-d H:i:s') . '\' where reference = ?', [$enforcement_reference]);
        }
    }

}
