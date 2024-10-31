jQuery(document).ready(function($) {
  $.ajax({
    url: ycsc.ajaxurl,
    data: {
        'action': 'get_base_url'
    },
    success: function(data) {
        var baseUrl = data;
        // console.log(baseUrl);

        var utilsScript = baseUrl + "assets/js/utils.js";
        

        if ($("#billing_phone") && $("#billing_phone").length ) {
          var iti = window.intlTelInput(document.querySelector("#billing_phone"), {
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
      iti.promise.then(function() {
        $('#billing_phone').val(iti.getNumber().replace('+',''));
      });		
          $('#billing_phone').on('blur', function () {
              $(this).val(iti.getNumber().replace('+',''));
          });
          $('.intl-tel-input').css('display', 'block');
      $(document).on('change', '#billing_country', function(e) {
        iti.setCountry(this.value);
      });
      iti.setCountry($('#billing_country').val());						
      }
      }
});

});