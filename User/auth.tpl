<div class="fx_auth_form" fx:template="auth_form" fx:of="auth_form">
    {apply floxim.form.form:form with $form /}
</div>

<div class="fx_recover_form" fx:template="recover_form" fx:of="recover_form">
    {apply floxim.form.form:form with $form /}
</div>

<div fx:template="greet" fx:of="greet" class="fx_user_greet">
    <span class="fx_user_greet_hello">{%hello}Hello, {/%}</span> 
    <a class="fx_profile_link" href="{$profile_url}" fx:omit="!$profile_url">
        <span class="fx_user_greet_user_name">{$user.name}</span>
    </a>
    <a class="fx_logout_link" href="{$logout_url}">{%logout}Log out{/%}</a>
</div>

<div 
    class="cross_site_auth_form" 
    fx:template="cross_site_auth_form" 
    fx:of="cross_site_auth_form"
    data-target_location="{$target_location}">
        {js bundle="admin"}
            FX_JQUERY_PATH
            cross_site_auth.js
        {/js}
        <style type="text/css">
            .cross_site_auth_form {
                opacity:0.1;
            }
            .cross_site_auth_form iframe {width:1000px; height:50px;}
        </style>
        <form 
            fx:each="$hosts as $host" 
            method="post"
            action="http://{$host}{$auth_url}" 
            target="iframe_{$position}">
                <iframe name="iframe_{$position}"></iframe><br />
                <input type="hidden" name="session_key" value="{$session_key /}" />
                <input type="submit" />
        </form>
</div>
                
<div fx:template="popup" fx:of="auth_form">
    <div class="fx_user_greet not-for-modal">
        {set $login_item}<span class="login">{%login}Войти{/%}</span>{/set}
        {apply floxim.ui.menu:menu with $items = array(
            array( 'name' => $login_item )
        ) /}
    </div>
    <div class="for-modal login_form" style="display:none;">
        {apply floxim.form.form:form with $form /}
    </div>
</div>