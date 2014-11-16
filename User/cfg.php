<?php
return array(
    'actions' => array(
        '*auth_form' => array(
            'settings' => array(
                'redirect_location_type' => array(
                    'label' => fx::alang('After login'),
                    'type' => 'select',
                    'values' => array(
                        array('refresh', fx::alang('Refresh current page')),
                        array('home', fx::alang('Redirect to homepage')),
                        array('custom', fx::alang('Redirect to custom URL...'))
                    )
                ),
                'redirect_location_custom' => array(
                    'label' => fx::alang('Target URL'),
                    'type' => 'string',
                    'parent' => array( 'redirect_location_type' => 'custom' )
                )
            )
        )
    )
);