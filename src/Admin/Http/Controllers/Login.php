<?php

namespace Igniter\Admin\Http\Controllers;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Facades\AdminHelper;
use Igniter\Admin\Facades\Template;
use Igniter\Flame\Exception\ValidationException;
use Igniter\User\Facades\AdminAuth;
use Igniter\User\Models\User;
use Illuminate\Support\Facades\Mail;

class Login extends AdminController
{
    public $bodyClass = 'page-login';

    public function __construct()
    {
        $this->middleware('throttle:'.config('igniter-auth.rateLimiter', '6,1'));
        parent::__construct();
    }

    public function index()
    {
        if (AdminAuth::isLogged()) {
            return AdminHelper::redirect('dashboard');
        }

        Template::setTitle(lang('igniter::admin.login.text_title'));

        return $this->makeView('auth.login');
    }

    public function reset()
    {
        if (AdminAuth::isLogged()) {
            return AdminHelper::redirect('dashboard');
        }

        $code = input('code');
        if (strlen($code) && !User::whereResetCode(input('code'))->first()) {
            flash()->error(lang('igniter::admin.login.alert_failed_reset'));

            return AdminHelper::redirect('login');
        }

        Template::setTitle(lang('igniter::admin.login.text_password_reset_title'));

        $this->vars['resetCode'] = input('code');

        return $this->makeView('igniter.admin::auth.reset');
    }

    public function onLogin()
    {
        $data = $this->validate(post(), [
            'email' => ['required', 'email'],
            'password' => ['required', 'min:6'],
        ], [], [
            'email' => lang('igniter::admin.login.label_email'),
            'password' => lang('igniter::admin.login.label_password'),
        ]);

        if (!AdminAuth::attempt(array_only($data, ['email', 'password']), true)) {
            throw new ValidationException(['username' => lang('igniter::admin.login.alert_login_failed')]);
        }

        session()->regenerate();

        return $this->createResponse(($redirectUrl = input('redirect'))
            ? AdminHelper::redirect($redirectUrl)
            : AdminHelper::redirectIntended('dashboard'));
    }

    public function onRequestResetPassword()
    {
        $data = $this->validate(post(), [
            'email' => ['required', 'email:filter', 'max:96'],
        ], [], [
            'email' => lang('igniter::admin.label_email'),
        ]);

        if ($user = User::whereEmail($data['email'])->first()) {
            if (!$user->resetPassword()) {
                throw new ValidationException(['email' => lang('igniter::admin.login.alert_failed_reset')]);
            }
            $data = [
                'staff_name' => $user->name,
                'reset_link' => admin_url('login/reset?code='.$user->reset_code),
            ];
            Mail::queueTemplate('igniter.admin::_mail.password_reset_request', $data, $user);
        }

        flash()->success(lang('igniter::admin.login.alert_email_sent'));

        return $this->createResponse(AdminHelper::redirect('login'));
    }

    public function onResetPassword()
    {
        $data = $this->validate(post(), [
            'code' => ['required'],
            'password' => ['required', 'min:6', 'max:32', 'same:password_confirm'],
            'password_confirm' => ['required'],
        ], [], [
            'code' => lang('igniter::admin.login.label_reset_code'),
            'password' => lang('igniter::admin.login.label_password'),
            'password_confirm' => lang('igniter::admin.login.label_password_confirm'),
        ]);

        $code = array_get($data, 'code');
        $user = User::whereResetCode($code)->first();

        if (!$user || !$user->completeResetPassword($data['code'], $data['password'])) {
            throw new ValidationException(['password' => lang('igniter::admin.login.alert_failed_reset')]);
        }

        Mail::queueTemplate('igniter.admin::_mail.password_reset', [
            'staff_name' => $user->name,
        ], $user);

        flash()->success(lang('igniter::admin.login.alert_success_reset'));

        return $this->createResponse(AdminHelper::redirect('login'));
    }

    protected function createResponse($redirectResponse): array
    {
        return request()->ajax()
            ? ['X_IGNITER_REDIRECT' => $redirectResponse->getTargetUrl()]
            : $redirectResponse;
    }
}
