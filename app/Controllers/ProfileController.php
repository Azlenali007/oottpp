<?php
/**
 * Profile & Security Controller
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Core\View;
use App\Core\Database;
use App\Services\AuthService;
use App\Helpers\Validator;
use App\Helpers\ResponseHelper;
use Exception;

class ProfileController {
    public function index(Request $request): void {
        $user = AuthService::user();

        echo View::render('user/profile', [
            'title' => 'My Profile - OTPPro',
            'user' => $user,
            'csrf_token' => Session::getCsrfToken(),
            'success' => Session::getFlash('success'),
            'error' => Session::getFlash('error'),
        ], 'layouts/user');
    }

    public function updateProfile(Request $request): void {
        $userId = (int) Session::get('user_id');

        $validator = Validator::make($request->input(), [
            'name' => 'required|min:2|max:50',
        ]);

        if ($validator->fails()) {
            ResponseHelper::error('Validation failed', 422, $validator->errors());
            return;
        }

        Database::execute("
            UPDATE users SET name = :name, phone = :phone WHERE id = :id
        ", [
            ':name' => (string) $request->input('name'),
            ':phone' => (string) $request->input('phone'),
            ':id' => $userId,
        ]);

        Session::set('user_name', (string) $request->input('name'));

        ResponseHelper::success(null, 'Profile updated successfully.');
    }

    public function changePassword(Request $request): void {
        $userId = (int) Session::get('user_id');

        $validator = Validator::make($request->input(), [
            'current_password' => 'required',
            'new_password' => 'required|min:8',
        ]);

        if ($validator->fails()) {
            ResponseHelper::error('Validation failed', 422, $validator->errors());
            return;
        }

        try {
            AuthService::changePassword(
                $userId,
                (string) $request->input('current_password'),
                (string) $request->input('new_password')
            );
            ResponseHelper::success(null, 'Password updated successfully.');
        } catch (Exception $e) {
            ResponseHelper::error($e->getMessage(), 400);
        }
    }
}
