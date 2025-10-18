(function($) {
    $(document).ready(function() {
        var form = $('#adp-filter-form');
        var resultsWrapper = $('#adp-result-wrapper');
        var searchBtn = form.find('.adp-btn-primary');
        var originalBtnText = searchBtn.text();

        // Debounce for search input
        var debounceTimer;
        form.find('input, select').on('input change', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function() {
                form.trigger('submit');
            }, 500);
        });

        form.on('submit', function(e) {
            e.preventDefault();
            var formData = form.serializeArray();
            
            var data = {
                action: 'adp_filter',
                nonce: adp_ajax.nonce,
                paged: 1, // Reset to page 1
                cols: resultsWrapper.data('cols') || 3
            };
            $.each(formData, function(i, field) {
                data[field.name] = field.value;
            });

            searchBtn.attr('disabled', true).html('<i class="fa-solid fa-spinner fa-spin-pulse"></i> جستجو...');
            resultsWrapper.css('opacity', 0.5);

            $.post(adp_ajax.ajax_url, data, function(response) {
                if (response.success) {
                    resultsWrapper.html(response.data.html);
                } else {
                    resultsWrapper.html('<p class="adp-empty">خطا یا هیچ نتیجه‌ای یافت نشد.</p>');
                }
            }).always(function() {
                searchBtn.attr('disabled', false).text(originalBtnText);
                resultsWrapper.css('opacity', 1);
            });
        });

        $('#adp-reset').on('click', function() {
            // بازنشانی فیلترها و reload صفحه
            window.location.href = window.location.pathname;
        });

        // جدید: انیمیشن لود نتایج
        resultsWrapper.on('load', function() {
            $(this).fadeIn(500);
        });

        // تابع کمکی برای خواندن Cookie
        function getCookie(name) {
            var nameEQ = name + '=';
            var ca = document.cookie.split(';');
            for (var i = 0; i < ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0) === ' ') c = c.substring(1, c.length);
                if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
            }
            return null;
        }
    });
})(jQuery);
