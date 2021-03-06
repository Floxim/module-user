<?php
namespace Floxim\User\User;

use Floxim\Floxim\System\Fx as fx;

class Entity extends \Floxim\Main\Content\Entity
{

    static public function load()
    {
        $session = fx::data('session')->load();
        $user = null;
        if ($session && $session['user_id']) {
            $session->setCookie();
            $user = fx::data('floxim.user.user', $session['user_id']);
        }

        if (!$user) {
            $user = fx::data('floxim.user.user')->create();
        }

        fx::env()->setUser($user);
        return $user;
    }
    
    
    protected $session = null;
    
    public function getSession()
    {
        if (is_null($this->session)) {
            $this->session = fx::data('session')->load();
            if (!$this->session) {
                $this->session = fx::data('session')->start([], false);
            }
        }
        return $this->session;
    }

    public function login($login, $password, $remember = true)
    {
        $user = fx::data('floxim.user.user')->getByLogin($login);
        
        if (!$user || !$user['password'] || crypt($password, $user['password']) !== $user['password']) {
            return false;
        }
        //fx::log($this, $user);
        if ($user !== $this) {
            // manually replace all fields by real user's fields
            $this->data = array();
            $this->modified = array();
            foreach ($user->get() as $f => $v) {
                $this->data[$f] = $v;
            }
        }
        $this->createSession($remember);
        fx::env()->set('user', $this);
        return true;
    }

    public function getLogoutUrl()
    {
        return fx::path()->http('@home/~ajax/floxim.user.user:logout/');
    }


    public function logout()
    {
        $session = fx::data('session');
        fx::trigger(
            'before_logout', 
            array(
                'user' => $this,
                'session' => $session
            )
        );
        $session->stop();
    }

    public function isAdmin()
    {
        return (bool)$this['is_admin'];
    }

    public function createSession($remember = 0, $params = array())
    {
        $session = fx::data('session')->start(array(
            'user_id'  => $this['id'],
            // admins have one cross-site session
            'site_id'  => $this->isAdmin() ? null : fx::env('site_id'),
            'remember' => $remember,
            'params' => $params
        ));
        return $session;
    }


    protected function beforeSave()
    {
        parent::beforeSave();
        if ($this->isModified('password') && !empty($this['password'])) {
            $this->setPayload('plain_password', $this['password']);
            $this['password'] = crypt($this['password'], uniqid(mt_rand(), true));
        } else {
            $this->setNotModified('password');
        }
    }

    public function isGuest()
    {
        return !$this['id'];
    }

    public function getAuthForm()
    {
        //$form = new \Floxim\Form\Form(array('id' => 'auth_form'));
        $form = fx::data('floxim.form.form')->create();
        $form['id'] = 'auth_form';
        $form->addFields(array(
            'email'    => array(
                'label'      => 'E-mail',
                'validators' => 'email -l'
            ),
            'password' => array(
                'type'  => 'password',
                'label' => fx::lang('Password')
            ),
            'remember' => array(
                'type'  => 'checkbox',
                'label' => fx::lang('Remember me')
            ),
            'submit'   => array(
                'type'  => 'submit',
                'label' => fx::lang('Log in')
            )
        ));
        return $form;
    }

    public function generatePassword()
    {
        $letters = '1234567890abcdefghijklmnopqrstuvwxyz';
        $specials = '!#$%&*@~';
        $res = '';
        $length = rand(6, 9);
        $special_pos = rand(1, $length - 1);
        foreach (range(0, $length) as $n) {

            if ($n == $special_pos) {
                $chars = $specials;
            } else {
                $chars = $letters;
            }
            $chars = str_split($chars);
            shuffle($chars);

            $letter_index = array_rand($chars);
            $letter = $chars[$letter_index];
            if (preg_match("~[a-z]~", $letter) && rand(0, 3) === 3) {
                $letter = strtoupper($letter);
            }
            $res .= $letter;
        }
        return $res;
    }
    
    public function getFormFields() 
    {
        $fields = fx::collection(parent::getFormFields());
        $pass_field = $fields->findOne('name', 'password');
        $fields->remove($pass_field);
        $fields[]= array(
            'name' => 'password',
            'type' => 'password',
            'label' => $pass_field['label']
        );
        $fields[]= array(
            'name' => 'confirm_password',
            'type' => 'password',
            'label' => fx::alang('Confirm').' '. $pass_field['label']
        );
        return $fields;
    }
    
    public function can($action, $target) {
        $method = 'can'.fx::util()->underscoreToCamel($action);
        if (method_exists($this, $method)) {
            $res = $this->$method($target);
            if (is_bool($res)) {
                return $res;
            }
        }
        if ($this->isAdmin()) {
            return true;
        }
        $event_res = fx::trigger('checkAbility', array('user' => $this, 'action' => $action, 'target' => $target));
        return $event_res;
    }
    
    public function canSeeCreateForm($entity) {
        if ($this->isGuest() && $entity->isInstanceOf('floxim.user.user')) {
            return true;
        }
    }
    
    public function canCreate($entity) {
        if ($this->isGuest() && $entity->isInstanceOf('floxim.user.user')) {
            return true;
        }
    }
    
    public function validate() {
        if ($this->isModified('email')) {
            $existing = fx::data('floxim.user.user')
                ->where('email', $this['email'])
                ->where('id', $this['id'], '!=')
                ->one();
            if ($existing) {
                $this->invalid(
                    fx::alang('This email is already used', 'system'), 
                    'email'
                );
            }
        }
        
        if ($this->isModified('password')) {
            
            $password = $this->getPayload('plain_password');
            if (is_null($password)) {
                $password = $this['password'];
            }
            if (!empty($password)) {
                $confirm = $this->getPayload('confirm_password');
                if (!is_null($confirm) && $confirm !== $password) {
                    $this->invalid(
                        fx::alang('Passwords do not match', 'system'),
                        'confirm_password'
                    );
                }
            } elseif (!$this['id']) {
                $this->invalid(
                    'Passwords can not be empty',
                    'password'
                );
            }
        }
        $pres = parent::validate();
        return $pres;
    }
}