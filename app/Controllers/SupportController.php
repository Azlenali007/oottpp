<?php
/**
 * Customer Support Controller
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Core\View;
use App\Core\Database;
use App\Services\AuthService;
use App\Helpers\Security;
use App\Helpers\Validator;
use App\Helpers\ResponseHelper;

class SupportController {
    public function index(Request $request): void {
        $user = AuthService::user();
        $userId = (int) $user['id'];

        $tickets = Database::query("
            SELECT * FROM support_tickets
            WHERE user_id = :uid
            ORDER BY created_at DESC
            LIMIT 20
        ", [':uid' => $userId]);

        echo View::render('user/support', [
            'title' => 'Support Desk - OTPPro',
            'user' => $user,
            'tickets' => $tickets,
            'csrf_token' => Session::getCsrfToken(),
        ], 'layouts/user');
    }

    public function createTicket(Request $request): void {
        $userId = (int) Session::get('user_id');

        $validator = Validator::make($request->input(), [
            'subject' => 'required|min:5|max:150',
            'category' => 'required|in:order_issue,payment_issue,service_issue,account_issue,other',
        ]);

        if ($validator->fails()) {
            ResponseHelper::error('Validation failed', 422, $validator->errors());
            return;
        }

        $ticketNumber = Security::generateTicketNumber();

        Database::execute("
            INSERT INTO support_tickets (
                ticket_number, user_id, subject, category, status, priority, created_at, updated_at
            ) VALUES (
                :tnum, :uid, :sub, :cat, 'open', 'medium', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
            )
        ", [
            ':tnum' => $ticketNumber,
            ':uid' => $userId,
            ':sub' => (string) $request->input('subject'),
            ':cat' => (string) $request->input('category'),
        ]);

        ResponseHelper::success([
            'ticket_number' => $ticketNumber,
        ], 'Support ticket created successfully. Our team will review it shortly.');
    }
}
