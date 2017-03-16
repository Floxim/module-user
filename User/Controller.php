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
        if ($this->getParam('ajax')) {
            $this->ajaxForm($form);
        }
        
        $this->trigger('form_ready', array('form' => $form));
        
        $recover_url = self::getFullRecoverUrl();
        if ($recover_url) {
            $form->addMessage(
                '<p>Если вы забыли пароль, его можно <a href="'.$recover_url.'">восстановить</a>!</p>'
            );
        }
        
        if ($form->isSent() && !$form->hasErrors()) {
            $vals = $form->getValues();
            if (!$user->login($vals['email'], $vals['password'], $vals['remember'])) {
                $form->addError( fx::lang('User not found or password is wrong'), 'password');
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
                    fx::http()->redirect( '@home/~ajax/floxim.user.user:cross_site_auth_form');
                }
                fx::http()->redirect($target_location);
            }
        }
        
        return array(
            'form' => $form
        );
    }
    
    public static function getFullRecoverUrl()
    {
        $recover_url = fx::config('user.recover_url');
        if (!$recover_url) {
            return false;
        }
        $recover_url = 'http://'.$_SERVER['HTTP_HOST'].$recover_url;
        return $recover_url;
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
            fx::http()->redirect('@home');
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
            'auth_url'        => fx::path()->http('@home/~ajax/floxim.user.user:cross_site_auth'),
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
                $user = fx::data('floxim.user.user', $session['user_id']);
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
        if (isset($_GET['recover_token'])) {
            $token = fx::data('floxim.user.recover_token')->where('token', $_GET['recover_token'])->one();
            
            if ($token && $token['user']) {
                $form = fx::data('floxim.form.form')->generate();
                $form = $this->ajaxForm($form);
                $form->addFields(array(
                    'password'  => array(
                        'label'      => 'Пароль',
                        'type'      => 'password'
                    ),
                    'password_repeat'  => array(
                        'label'      => 'Подтверждение пароля',
                        'type'      => 'password'
                    ),
                    'submit' => array(
                        'type'  => 'submit',
                        'label' => 'Установить пароль'
                    )
                ));
                if ($form->isSent()) {
                    if (!$form->password) {
                        $form->addError('Необходимо задать пароль');
                    } elseif ($form->password !== $form->password_repeat) {
                        $form->addError('Введенные пароли не совпадают');
                    }
                    if (!$form->hasErrors()) {
                        $user = $token['user'];
                        $user['password'] = $form->password;
                        $user->save();
                        fx::user()->login($user['email'], $form->password, true);
                        $token->delete();
                        $form->finish(
                            '<p>Пароль успешно изменен!</p>'.
                            '<script type="text/javascript">setTimeout(function() {'
                                . 'document.location.href = "/"'.
                            '}, 3000);</script>'
                        );
                    }
                }
                $this->assign('form', $form);
                return;
            }
        }
        //$form = new \Floxim\Form\Form();
        $form = fx::data('floxim.form.form')->generate();
        $form = $this->ajaxForm($form);
        $c_email = $this->getParam('email', fx::input('session', 'email'));
        $form->addFields(array(
            'email'  => array(
                'label'      => 'E-mail',
                'validators' => 'email',
                'value'      => $c_email
            ),
            'submit' => array(
                'type'  => 'submit',
                'label' => 'Готово'
            )
        ));
        $form->addMessage(
            '<p>Введите адрес электронной почты, с которым вы регистрировались, '.
                'и мы вышлем на него ссылку для установки нового пароля!</p>',
            'before'
        );
        
        if ($form->isSent() && !$form->hasErrors()) {
            
            $user = fx::data('floxim.user.user')->getByLogin($form->email);
            if (!$user) {
                $form->addError('Пользователь не найден');
            } else {
                $token = fx::data('floxim.user.recover_token')->create([
                    'token' => fx::util()->uid(),
                    'token_user_id' => $user['id'],
                    'expire_date' => time() + 60*60*24 // 1 day
                ]);
                $token->save();
                $form->finish(
                    '<p>Письмо со ссылкой для смены пароля отправлено на адрес '
                        .$user['email'].'</p>'
                );
                $mailer = fx::mail();
                $from_addr = fx::config('mail.from_address');
                $from_name = fx::config('mail.from_name');
                $mailer->from($from_addr, $from_name);
                $host = $_SERVER['HTTP_HOST'];
                //$link = 'http://'.$host.$_SERVER['REQUEST_URI'].'?recover_token='.$token['token'];
                $link = self::getFullRecoverUrl().'?recover_token='.$token['token'];
                $mailer->to($form->email)
                       ->subject($_SERVER['HTTP_HOST'].' - восстановление пароля')
                       ->message(
                            '<p>Здравствуйте, '.$user['name'].'!</p>'.
                            '<p>Для смены пароля на сайте '.$host.' перейдите по ссылке:</p>'.
                            '<p><a href="'.$link.'"><b>Задать новый пароль</b></a></p>'.
                            '<p>Ссылка действительна 24 часа.</p>'.
                            '<p>Если вы не запрашивали восстановление пароля, просто проигнорируйте это письмо.</p>'
                        )
                       ->send();
            }
        }
        return array('form' => $form);
    }

    public function doLogout()
    {
        $user = fx::user();
        $user->logout();
        $back_url = $this->getParam('back_url', '@home/');
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