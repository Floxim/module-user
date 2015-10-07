<?php
return array(
    'actions' => array(
        '*auth_form' => array(
            'name' => fx::alang('Login form', 'floxim.main.user'),
            'settings' => array(
                'redirect_location_type' => array(
                    'label' => fx::alang('After login', 'floxim.main.user'),
                    'type' => 'select',
                    'values' => array(
                        array('refresh', fx::alang('Refresh current page')),
                        array('home', fx::alang('Redirect to homepage')),
                        array('custom', fx::alang('Redirect to custom URL...'))
                    )
                ),
                'ajax' => array(
                    'type' => 'checkbox',
                    'label' => fx::alang('Use AJAX'),
                    'value' => 1
                ),
                'redirect_location_custom' => array(
                    'label' => fx::alang('Target URL'),
                    'type' => 'string',
                    'parent' => array( 'redirect_location_type' => 'custom' )
                )
            )
        ),
        '*cross_site*' => array(
            'disabled' => true
        ),
        '*greet' => array(
            'name' => fx::alang('Greet and logout widget', 'floxim.main.user')
        ),
        '*recover_form' => array(
            'name' => fx::alang('Recover password form', 'floxim.main.user')
        ),
        '*form_create*' => array(
            'name' => fx::alang('Registration form', 'floxim.main.user'),
            'settings' => array(
                'force_login' => array(
                    'type' => 'checkbox',
                    'label' => fx::alang('Login after registration')
                )
            )
        )
    )
);