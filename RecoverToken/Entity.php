<?php
namespace Floxim\User\RecoverToken;

use Floxim\Floxim\System\Fx as fx;

class Entity extends \Floxim\Floxim\Component\Basic\Entity
{
    public function isExpired()
    {
        $expire_date = $this['expire_date'];
        if (!$expire_date) {
            return false;
        }
        $expire_time = fx::date($expire_date, 'U')*1;
        return $expire_time < time();
    }
}