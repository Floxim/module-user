(function($) {
    $(function() {
        var $container = $('.cross_site_auth_form');
        var $forms = $('form', $container);
        var go_on = function() {
            var target_location = $container.data('target_location');
            if (target_location && target_location !== document.location.href) {
                document.location.href = target_location;
            }
        };
        if ($forms.length === 0) {
            go_on();
            return;
        }
        $forms.each(function() {
            $(this).submit();
        });
        setTimeout(go_on, 1000);
    });
})(jQuery);