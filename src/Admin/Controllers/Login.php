<?php

namespace Igniter\Admin\Controllers;

use Igniter\Admin\Facades\AdminAuth;
use Igniter\Admin\Facades\Template;
use Igniter\Admin\Models\User;
use Igniter\Admin\Traits\ValidatesForm;
use Igniter\Flame\Exception\ValidationException;
use Illuminate\Support\Facades\Mail;

class Login extends \Igniter\Admin\Classes\AdminController
{
    use ValidatesForm;

    protected $requireAuthentication = false;

    public $bodyClass = 'page-login';

    public function __construct()
    {
        parent::__construct();

        $this->middleware('throttle:'.config('igniter.system.authRateLimiter', '6,1'));
    }

    public function index()
    {
        if (AdminAuth::isLogged())
            return $this->redirect('dashboard');

        Template::setTitle(lang('igniter::admin.login.text_title'));

        return $this->makeView('auth.login');
    }

    public function reset()
    {
        if (AdminAuth::isLogged()) {
            return $this->redirect('dashboard');
        }

        $code = input('code');
        if (strlen($code) && !User::whereResetCode(input('code'))->first()) {
            flash()->error(lang('igniter::admin.login.alert_failed_reset'));

            return $this->redirect('login');
        }

        Template::setTitle(lang('igniter::admin.login.text_password_reset_title'));

        $this->vars['resetCode'] = input('code');

        return $this->makeView('auth/reset');
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

        if (!AdminAuth::attempt(array_only($data, ['email', 'password']), true))
            throw new ValidationException(['username' => lang('igniter::admin.login.alert_login_failed')]);

        session()->regenerate();

        if ($redirectUrl = input('redirect'))
            return $this->redirect($redirectUrl);

        return $this->redirectIntended('dashboard');
    }

    public function onRequestResetPassword()
    {
        $data = post();

        $this->validate($data, [
            'email' => ['required', 'email:filter', 'max:96'],
        ], [], [
            'email' => lang('igniter::admin.label_email'),
        ]);

        if ($user = User::whereEmail(post('email'))->first()) {
            if (!$user->resetPassword())
                throw new ValidationException(['email' => lang('igniter::admin.login.alert_failed_reset')]);
            $data = [
                'staff_name' => $user->name,
                'reset_link' => admin_url('login/reset?code='.$user->reset_code),
            ];
            Mail::queue('igniter.admin::_mail.password_reset_request', $data, function ($message) use ($user) {
                $message->to($user->email, $user->name);
            });
        }

        flash()->success(lang('igniter::admin.login.alert_email_sent'));

        return $this->redirect('login');
    }

    public function onResetPassword()
    {
        $data = post();

        $this->validate($data, [
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

        if (!$user || !$user->completeResetPassword($code, post('password')))
            throw new ValidationException(['password' => lang('igniter::admin.login.alert_failed_reset')]);

        $data = [
            'staff_name' => $user->name,
        ];

        Mail::queue('igniter.admin::_mail.password_reset', $data, function ($message) use ($user) {
            $message->to($user->email, $user->name);
        });

        flash()->success(lang('igniter::admin.login.alert_success_reset'));

        return $this->redirect('login');
    }
}
