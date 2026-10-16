<?php
/**
 * Payment Deposit Controller
 * 
 * Handles recharge initiation and status polling.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Services\PaymentService;
use App\Helpers\Validator;
use App\Helpers\ResponseHelper;
use Exception;

class PaymentController {
    public function deposit(Request $request): void {
        $userId = (int) Session::get('user_id');

        $validator = Validator::make($request->input(), [
            'amount' => 'required|numeric|min:10',
        ]);

        if ($validator->fails()) {
            ResponseHelper::error('Validation failed', 422, $validator->errors());
            return;
        }

        try {
            $amount = (float) $request->input('amount');
            $gateway = $request->input('gateway', 'upi_gateway');

            $result = PaymentService::initiateDeposit($userId, $amount, $gateway);
            ResponseHelper::success($result, 'Recharge initiated');
        } catch (Exception $e) {
            ResponseHelper::error($e->getMessage(), 400);
        }
    }
}
