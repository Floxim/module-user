<?php
namespace Floxim\User\User;

use Floxim\Floxim\System\Fx as fx;

class Controller extends \Floxim\Main\Content\Controller
{
    public function doAuthForm()
    {
        $user = fx::user();

        if (!$user->isGuest()) {
            if (!fx::isAdmin()) {
                return false;
            }
            //$this->_meta['hidden'] = true;
        }

        $form = $user->getAuthForm();

        if ($form->isSent() && !$form->hasErrors()) {
            $vals = $form->getValues();
            if (!$user->login($vals['email'], $vals['password'], $vals['remember'])) {
                $form->addError('User not found or password is wrong', 'email');
            } else {
                $location = $_SERVER['REQUEST_URI'];
                if ($location === '/floxim/') {
                    $location = '/';
                }
                switch ($this->getParam('redirect_location_type')) {
                    case 'refresh': default:
                        $target_location = $location;
                        break;
                    case 'home':
                        $target_location = '/';
                        break;
                    case 'custom':
                        $target_location = $this->getParam('redirect_location_custom');
                        if (empty($target_location)) {
                            $target_location = '/';
                        }
                        break;
                }
                // send admin to cross-auth page
                if ($user->isAdmin()) {
                    fx::input()->setCookie('fx_target_location', $target_location);
                    fx::http()->redirect('/~ajax/user:cross_site_auth_form');
                }
                fx::http()->redirect($target_location);
            }
        }

        return array(
            'form' => $form
        );
    }

    /**
     * Show form to authorize user on all sites
     */
    public function doCrossSiteAuthForm()
    {
        if (!preg_match("~/\~ajax~", $_SERVER['REQUEST_URI'])) {
            return false;
        }
        if (!fx::user()->isAdmin()) {
            fx::http()->redirect('/');
        }
        $sites = fx::data('site')->all();
        $hosts = array();
        foreach ($sites as $site) {
            foreach ($site->getAllHosts() as $host) {
                if ($host === fx::env('host')) {
                    continue;
                }
                $hosts[] = $host;
            }
        }
        fx::env('ajax', false);
        $target_location = fx::input()->fetchCookie('fx_target_location');
        // unset cookie
        fx::input()->setCookie('fx_target_location', '', 1);
        if (!$target_location) {
            $target_location = '/';
        }
        if (count($hosts) === 0) {
            fx::http()->redirect($target_location);
        }
        return array(
            'hosts'           => $hosts,
            'auth_url'        => '/~ajax/user:cross_site_auth',
            'target_location' => $target_location,
            'session_key'     => fx::data('session')->load()->get('session_key')
        );
    }

    public function doCrossSiteAuth()
    {
        if (isset($_POST['email']) && isset($_POST['password'])) {
            fx::user()->login($_POST['email'], $_POST['password']);
        } elseif (isset($_POST['session_key'])) {
            $session = fx::data('session')->getByKey($_POST['session_key']);
            if ($session) {
                $session->setCookie();
                $user = fx::data('user', $session['user_id']);
                return "Hello, " . $user['name'] . '!<br /> ' . fx::env('host') . ' is glad to see you!';
            }
        }
    }

    public function doGreet()
    {
        $user = fx::user();
        if ($user->isGuest()) {
            return false;
        }
        return array(
            'user'       => $user,
            'logout_url' => $user->getLogoutUrl()
        );
    }

    public function doRecoverForm()
    {
        $form = new \Floxim\Form\Form();
        $form->addFields(array(
            'email'  => array(
                'label'      => 'E-mail',
                'validators' => 'email -l',
                'value'      => $this->getParam('email')
            ),
            'submit' => array(
                'type'  => 'submit',
                'label' => fx::lang('Send me new password')
            )
        ));
        if ($form->isSent() && !$form->hasErrors()) {
            $user = fx::data('user')->getByLogin($form->email);
            if (!$user) {
                $form->addError(fx::lang('User not found'), 'email');
            } else {
                $password = $user->generatePassword();
                $user['password'] = $password;
                $user->save();
                fx::data('session')->where('user_id', $user['id'])->delete();
                $mailer = fx::mail();
                $res = $mailer
                    ->to($form->email)
                    ->data('user', $user)
                    ->data('password', $password)
                    ->data('site', fx::env('site'))
                    ->template('user.password_recover')
                    ->send();
                if ($res) {
                    $form->addMessage('New password is sent to ' . $form->email);
                }
            }
        }
        return array('form' => $form);
    }

    public function doLogout()
    {
        $user = fx::user();
        $user->logout();
        $back_url = $this->getParam('back_url', '/');
        fx::http()->redirect($back_url, 302);
    }
    
    public function doFormCreate() {
        $this->onFormReady(function($e) {
            unset($e['form']['fields']['is_published']);
            unset($e['form']['fields']['avatar']);
            unset($e['form']['fields']['is_admin']);
            $e['form']['fields']['email']['required'] = true;
            $e['form']['fields']['name']['required'] = true;
        });
        if ($this->getParam('force_login') && fx::user()->isGuest()) {
            $this->onFormCompleted(function($e) {
               $form = $e['form'];
               $user = $e['entity'];
               $user->login($form->email, $form->password, true);
            });
        }
        return parent::doFormCreate();
    }
}