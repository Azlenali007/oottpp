<?php
/**
 * Authentication Controller
 * 
 * Handles user login, registration, logout, and Super Admin authentication.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\AuthService;
use App\Helpers\Validator;
use App\Helpers\ResponseHelper;
use Exception;

class AuthController {
    public function showLogin(Request $request): void {
        Session::start();
        if (Session::get('user_id')) {
            $resp = new Response();
            $resp->redirect('/dashboard');
            return;
        }

        echo View::render('auth/login', [
            'title' => 'Sign In - OTPPro',
            'csrf_token' => Session::getCsrfToken(),
            'error' => Session::getFlash('error'),
            'success' => Session::getFlash('success'),
        ], 'layouts/auth');
    }

    public function showAdminLogin(Request $request): void {
        Session::start();
        if (Session::get('user_role') === 'super_admin') {
            $resp = new Response();
            $resp->redirect('/admin/dashboard');
            return;
        }

        echo View::render('auth/admin_login', [
            'title' => 'Super Admin Portal - OTPPro',
            'csrf_token' => Session::getCsrfToken(),
            'error' => Session::getFlash('error'),
        ], 'layouts/auth');
    }

    public function login(Request $request): void {
        $validator = Validator::make($request->input(), [
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            if ($request->isAjax()) {
                ResponseHelper::error('Validation failed', 422, $validator->errors());
                return;
            }
            Session::setFlash('error', $validator->firstError());
            (new Response())->redirect('/login');
            return;
        }

        try {
            $user = AuthService::login(
                (string) $request->input('email'),
                (string) $request->input('password'),
                false
            );

            if ($request->isAjax()) {
                ResponseHelper::success([
                    'user' => [
                        'id' => $user['id'],
                        'name' => $user['name'],
                        'email' => $user['email'],
                        'role' => $user['role'],
                    ],
                    'redirect' => $user['role'] === 'super_admin' ? '/admin/dashboard' : '/dashboard',
                ], 'Login successful');
                return;
            }

            $redirectUrl = $user['role'] === 'super_admin' ? '/admin/dashboard' : '/dashboard';
            (new Response())->redirect($redirectUrl);
        } catch (Exception $e) {
            if ($request->isAjax()) {
                ResponseHelper::error($e->getMessage(), 401);
                return;
            }
            Session::setFlash('error', $e->getMessage());
            (new Response())->redirect('/login');
        }
    }

    public function adminLogin(Request $request): void {
        $validator = Validator::make($request->input(), [
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            if ($request->isAjax()) {
                ResponseHelper::error('Validation failed', 422, $validator->errors());
                return;
            }
            Session::setFlash('error', $validator->firstError());
            (new Response())->redirect('/admin/login');
            return;
        }

        try {
            $user = AuthService::login(
                (string) $request->input('email'),
                (string) $request->input('password'),
                true // adminOnly = true
            );

            if ($request->isAjax()) {
                ResponseHelper::success(['redirect' => '/admin/dashboard'], 'Super Admin authorized');
                return;
            }

            (new Response())->redirect('/admin/dashboard');
        } catch (Exception $e) {
            if ($request->isAjax()) {
                ResponseHelper::error($e->getMessage(), 401);
                return;
            }
            Session::setFlash('error', $e->getMessage());
            (new Response())->redirect('/admin/login');
        }
    }

    public function showRegister(Request $request): void {
        Session::start();
        if (Session::get('user_id')) {
            $resp = new Response();
            $resp->redirect('/dashboard');
            return;
        }

        echo View::render('auth/register', [
            'title' => 'Create Account - OTPPro',
            'csrf_token' => Session::getCsrfToken(),
            'error' => Session::getFlash('error'),
        ], 'layouts/auth');
    }

    public function register(Request $request): void {
        $validator = Validator::make($request->input(), [
            'name' => 'required|min:2|max:50',
            'email' => 'required|email',
            'password' => 'required|min:8',
        ]);

        if ($validator->fails()) {
            if ($request->isAjax()) {
                ResponseHelper::error('Validation failed', 422, $validator->errors());
                return;
            }
            Session::setFlash('error', $validator->firstError());
            (new Response())->redirect('/register');
            return;
        }

        try {
            $user = AuthService::register(
                (string) $request->input('name'),
                (string) $request->input('email'),
                (string) $request->input('password'),
                (string) $request->input('phone')
            );

            // Auto-login after registration
            AuthService::login(
                (string) $request->input('email'),
                (string) $request->input('password')
            );

            if ($request->isAjax()) {
                ResponseHelper::success(['redirect' => '/dashboard'], 'Account created successfully');
                return;
            }

            (new Response())->redirect('/dashboard');
        } catch (Exception $e) {
            if ($request->isAjax()) {
                ResponseHelper::error($e->getMessage(), 400);
                return;
            }
            Session::setFlash('error', $e->getMessage());
            (new Response())->redirect('/register');
        }
    }

    public function logout(Request $request): void {
        AuthService::logout();
        (new Response())->redirect('/login');
    }
}
