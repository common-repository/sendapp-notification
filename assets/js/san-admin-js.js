jQuery(function ($) {
    $("textarea.san-emoji").emojioneArea({
      pickerPosition: "bottom",
      tones: false,
      search: false
    });
    
	var baseUrl = document.getElementById('plugin-base-url').value;
	var utilsScript = baseUrl + "assets/js/utils.js";

    if ($( "#san_test_number" ) && $( "#san_test_number" ).length ) {
        var iti = window.intlTelInput(document.querySelector("#san_test_number"), {
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
        $('#san_test_number').on('blur', function () {
            $(this).val(iti.getNumber().replace('+',''));
        });
    }
    
       
      
	$('.nav-tab-wrapper a').click(function(event){
		event.preventDefault();
		var context = $(this).closest('.nav-tab-wrapper').parent();
		$('.nav-tab-wrapper li', context).removeClass('nav-tab-active');
		$(this).closest('li').addClass('nav-tab-active');
		$('.wp-tab-panels', context).hide();

		$( $(this).attr('href'), context ).show();
	});	
	$('.san-tab-wrapper .nav-tab-wrapper').each(function(){
		if ( $('.nav-tab-active', this).length )
			$('.nav-tab-active', this).click();
		else
			$('a', this).first().click();
	}); 
	$('.san-panel-footer input[type=submit]').click(function(event){
		$(this).parent().append('<img src="images/spinner-2x.gif">');
	});		
    $('#san-sortable-items')
        .accordion({
            header: "> li > header",
            active: false,
            collapsible: true,
            heightStyle: "content",
            activate: function (event, ui) {
				san_action($(this));
            }
        })
        .sortable({
            axis: "y",
            update: function (event, ui) {
                san_action($(this));
            }
        });
    $('.san-add-item').click(function () {
        var new_li = Date.now()/1000|0;
        var ul = $('#san-sortable-items');
        var li = '<li id="san_item_' + new_li + '"> <header> <i class="dashicons-before dashicons-arrow-down-alt2" aria-hidden="true"></i> New Rule </header> <div class="san-item-body"> <div class="san-body-left"> <p class="san-match"> <label for="keyword-match-' + new_li + '">Keyword Match</label> <select id="keyword-match-' + new_li + '" class="widefat" name="san_autoresponders[items][' + new_li + '][item_match]"> <option value="partial_all">Contain Keyword</option> <option value="match">Exact Match</option> <option value="partial">Beginning Sentence</option> </select> </p> <p class="san-keyword"> <label for="chat-keyword-' + new_li + '">Chat Keyword</label> <input type="text" id="chat-keyword-' + new_li + '" class="widefat" name="san_autoresponders[items][' + new_li + '][item_keyword]"> </p> </div> <div class="san-body-right"> <p> <label for="autoresponder-reply-' + new_li + '">Autoresponder Reply</label> <textarea rows="5" id="autoresponder-reply-' + new_li + '" class="widefat" name="san_autoresponders[items][' + new_li + '][item_reply]"></textarea> </p> <p class="san-upload-img"><input type="button" name="upload-btn" class="upload-btn button-secondary" data-id="item-img-' + new_li + '" value="Upload Image"><input type="text" name="san_autoresponders[items][' + new_li + '][item_img]" placeholder="Image URL (Max 1 MB)" class="image_url regular-text item-img-' + new_li + '"></p> </div> <div class="san-item-controls"> <a href="#" class="san-remove-item">Delete</a> </div> </div> </li>';
        ul.prepend(li);
        $('#san-sortable-items').accordion( "refresh" );
        san_action('#san-sortable-items');
        return false;
    });
    function san_action(el) {
        var items_sort = $(el).sortable('serialize', {key: 'sort'});
        $('#san-items-order').val(items_sort);
        $('.san-remove-item').click(function () {
            $('#san-sortable-items').accordion('option', {active: false});
            $(this).parents('li').remove();
            return false;
        });
    }
    $('.san-tab-wrapper').on("click", '.upload-btn', function(e) {
        e.preventDefault();
        localStorage.setItem("upload-btn-class", $(this).data('id'));
        var input_id = localStorage.getItem("upload-btn-class");
        var image = wp.media({ 
            title: 'Upload Image',
            multiple: false
        }).open()
        .on('select', function(e){
            var uploaded_image = image.state().get('selection').first();
            var image_url = uploaded_image.toJSON().url;
            $("."+input_id).val(image_url);
        });
    });
    $("#san_broadcast_target").change(function() {
        if($(this).val() === "custom") {
            $(".broadcast-list-wrapper").show();
        } else {
            $(".broadcast-list-wrapper").hide();
        }
	});
	$('.instance-desc > strong').click(function () {
		$(this).toggleClass('active');
		$('.instance-desc > div').toggle();
	});

	let token = $('#access_token').val();
	let instance = $('#instance_id').val();
	
    $('.ins-action').click(function (e) {
		let $this = $(this);
		let actionData = $(this).data('action');		
        let controlPopup = '';
		if(actionData == 'reconnect') {
			controlPopup += '<h2>Are you sure you want to reconnect instance?</h2>';
			controlPopup += '<div class="ins-btn-wrapper"><a href="#" class="button button-primary" id="ins-btn" data-button="reconnect">Reconnect</a> <a href="#" class="button button-secondary" rel="modal:close">Cancel</a></div>';
			controlPopup += '<div class="ins-results"></div>';
		}
		if(actionData == 'reboot') {
			controlPopup += '<h2>Are you sure you want to reboot instance?</h2>';
			controlPopup += '<div class="ins-btn-wrapper"><a href="#" class="button button-primary" id="ins-btn" data-button="reboot">Reboot</a> <a href="#" class="button button-secondary" rel="modal:close">Cancel</a></div>';
			controlPopup += '<div class="ins-results"></div>';
		}
		if(actionData == 'status') {
			controlPopup += '<h2>Connection Status</h2>';
			controlPopup += '<div class="ins-results"><div class="loader"></div></div>';
			$.getJSON('https://app.sendapp.cloud/api/reconnect?instance_id=' + instance + '&access_token=' + token, function(data) {
				let deviceStatus = '';
				if(data.data.avatar.includes('whatsapp')) {
					deviceStatus = 'Connected';
				} else {
					deviceStatus = 'Disconnected';
				}
				$('#control-modal').find('.ins-results').html('<div class="response">Phone ' + deviceStatus + '</div>');
			});			
		}
		if(actionData == 'reset') {
			controlPopup += '<h2>Are you sure you want to reset instance?</h2>';
			controlPopup += '<div class="ins-btn-wrapper"><a href="#" class="button button-primary" id="ins-btn" data-button="reset">Reset</a> <a href="#" class="button button-secondary" rel="modal:close">Cancel</a></div>';
			controlPopup += '<div class="ins-results"></div>';
		}
		if(actionData == 'webhook') {
			controlPopup += '<h2>Set new webhook url below:</h2>';
			controlPopup += '<div class="ins-btn-wrapper"><input type="url" id="ins-webhook" placeholder="https://webhook.site/sample.php"><a href="#" class="button button-primary" id="ins-btn" data-button="webhook">Submit</a></div>';
			controlPopup += '<div class="ins-results"></div>';
		}
        $('#control-modal').html('<div class="controlPopup">' + controlPopup + '</div>');
        $('#control-modal').modal();
    });
	$('#control-modal').on("click", '#ins-btn', function(e) {
		let $this = $(this);
		$this.parent().hide();
		$this.parents('.modal').find('.ins-results').html('<div class="loader"></div>');
		if($this.data('button') == 'reconnect') {
			$.getJSON('https://app.sendapp.cloud/api/reconnect?instance_id=' + instance + '&access_token=' + token, function(data) {
				$this.parents('.modal').find('.ins-results').html('<div class="response">Reconnect ' + data.message + '</div>');
			});			
		}
		if($this.data('button') == 'reboot') {
			$.getJSON('https://app.sendapp.cloud/api/reboot?instance_id=' + instance + '&access_token=' + token, function(data) {
				$this.parents('.modal').find('.ins-results').html('<div class="response-reboot">' + data.message + '. Please click "Generate QR Code" button and scan in 30 seconds<br><a href="#" class="button button-primary" id="ins-btn" data-button="generate">Generate QR Code</a></div></div>');
			});
		}
		if($this.data('button') == 'generate') {
			$.getJSON('https://app.sendapp.cloud/api/getqrcode?instance_id=' + instance + '&access_token=' + token, function(data) {
				$('#control-modal').find('.ins-results').html('<div class="response-qr"><img id="qr-code" src="' + data.base64 + '"></div>');
				setTimeout(function(e) {
					$('#control-modal').find('.response-qr').html('Close this popup if you have successfully scanned the qr code or retry the process again if you haven\'t');
				}, 30 * 1000)
			});			
		}
		if($this.data('button') == 'reset') {
			$.getJSON('https://app.sendapp.cloud/api/resetinstance?instance_id=' + instance + '&access_token=' + token, function(data) {
				$this.parents('.modal').find('.ins-results').html('<div class="response">' + data.message + '. Please check your new Instance ID on <a href="https://sendapp.live/plugin-wordpress-woocommerce-whatsapp-notification/">SendApp Dashboard Page</a> and update your old one on Device Settings tab.</div>');
			});			
		}
		if($this.data('button') == 'webhook') {
			let webhookUrl = $this.parents('.modal').find('#ins-webhook').val();
			$.getJSON('https://app.sendapp.cloud/api/setwebhook?webhook_url=' + webhookUrl + '&enable=true&instance_id=' + instance + '&access_token=' + token, function(data) {
				console.log(data);
				$this.parents('.modal').find('.ins-results').html('<div class="response">' + data.message + '</div>');
			});			
		}
    });
	$('.table-message-logs').on("click", '.log-resend', function(e) {
        e.preventDefault();
        $('input[name=san_resend_phone]').val( $(this).parents('tr').find('.log-phone').text() );
        $('input[name=san_resend_message]').val( $(this).parents('tr').find('.log-msg div').text() );
        $('input[name=san_resend_image]').val( $(this).parents('tr').find('.log-img').text() );
        $('input[name=san_resend_wa]').click();
    });
    var tbody = $('table.table-message-logs tbody');
    tbody.html($('tr',tbody).get().reverse());
    
    
	
});