jQuery(function($) {
    if ($("#edd-phone") && $("#edd-phone").length ) {
        $("#edd-phone").on("keypress keyup blur",function (event) {    
        	$(this).val($(this).val().replace(/[^\d].+/, ""));
        	if ((event.which < 48 || event.which > 57)) {
        		event.preventDefault();
        	}
        });

        var baseUrl = document.getElementById('plugin-base-url').value;
        var utilsScript = baseUrl + "assets/js/utils.js";

        var iti = window.intlTelInput(document.querySelector("#edd-phone"), {
          initialCountry: "auto",
          geoIpLookup: function(callback) {
            $.get('https://ipinfo.io', function() {}, "jsonp").always(function(resp) {
              var countryCode = (resp && resp.country) ? resp.country : "";
              callback(countryCode);
            });
          },
          utilsScript: utilsScript,
        });
        window.iti = iti;
        $('#edd-phone').on('blur', function () {
            $(this).val(iti.getNumber().replace('+',''));
        });
        $('.intl-tel-input').css('display', 'block');
    }
});