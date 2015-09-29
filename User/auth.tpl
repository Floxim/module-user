<div class="fx_auth_form" fx:template="auth_form" fx:of="user:auth_form">
    {apply form.form:form with $form /}
</div>

<div class="fx_recover_form" fx:template="recover_form" fx:of="user:recover_form">
    {apply form.form:form with $form /}
</div>

<div fx:template="greet" fx:of="user:greet" class="fx_user_greet">
    <span class="fx_user_greet_hello">{%hello}Hello, {/%}</span> 
    <a class="fx_profile_link" href="{$profile_url}" fx:omit="!$profile_url">
        <span class="fx_user_greet_user_name">{$user.name}</span>
    </a>
    <a class="fx_logout_link" href="{$logout_url}">{%logout}Log out{/%}</a>
</div>

<div 
    class="cross_site_auth_form" 
    fx:template="cross_site_auth_form" 
    fx:of="user:cross_site_auth_form"
    data-target_location="{$target_location}">
        <script type="text/javascript" src="<?= FX_JQUERY_PATH_HTTP ?>"></script>
        <script type="text/javascript" src="<?= $template_dir ?>cross_site_auth.js"></script>
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