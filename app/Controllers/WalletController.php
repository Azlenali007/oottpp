<?php
/**
 * Wallet Controller
 * 
 * Displays wallet balance, transaction logs, and deposit options.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Core\View;
use App\Services\AuthService;
use App\Services\WalletService;
use App\Helpers\ResponseHelper;

class WalletController {
    public function index(Request $request): void {
        $user = AuthService::user();
        $userId = (int) $user['id'];

        $wallet = WalletService::getWallet($userId);
        $transactions = WalletService::getTransactions($userId, 50);

        if ($request->isAjax()) {
            ResponseHelper::success([
                'wallet' => $wallet,
                'transactions' => $transactions,
            ]);
            return;
        }

        echo View::render('user/wallet', [
            'title' => 'My Wallet - OTPPro',
            'user' => $user,
            'wallet' => $wallet,
            'transactions' => $transactions,
            'csrf_token' => Session::getCsrfToken(),
        ], 'layouts/user');
    }
}
