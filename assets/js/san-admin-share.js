        jQuery( function( $ ){
            
            $( '.san-table > tbody').find('.wp-posts-list,.wp-products-list,.wp-customer-list' ).select2();
            
            function sanProcessingWA( ele ){
                $( ele ).append('<div id="san-overlay"><span>Processing, please wait...</span></div>');
            }
            
            function sanProcessCustAjax( form_data, sanArea, ksk, tdInfo ){
    
                $.ajax({
                    url: ycsc.ajaxurl,
                    type: 'POST',
                    contentType: false,
                    processData: false,
                    data: form_data,
                    beforeSend:function(){
                  
                         sanProcessingWA( sanArea );
                        
                    },
                    success: function ( res ) {
                 
                    },
                    error: function( XMLHttpRequest, textStatus, errorThrown ) {
                        
                        alert( textStatus + ' : ' + errorThrown );
                        $( sanArea ).find( '#san-overlay' ).remove();
                        
                    },
                    complete: function( res ) {
                        
                        $( sanArea ).find( '#san-overlay' ).remove();
                        $( ksk ).show();
                        setTimeout(function() { $( ksk ).hide(); }, 5000);
                        
                        if( res.responseText && res.status == 200 ){
                           
                            $( tdInfo ).addClass( 'san-res-info' ).text( 'Total '+res.responseText +' customer(s) processed.' );
                            
                        }
                        
                        else if( res.responseText == '' && res.status == 200 ){
                           
                            $( tdInfo ).addClass( 'san-res-info' ).text( 'Please check number!' );
                            
                        }
                        
                    }
                    
                });
               
            }
            
            function ycImport( list, button ){
                
                var $this       = $( button );
                var custList    = $( list );
                var form_data   = new FormData();
                
                form_data.append( 'action','yc_get_wccust' );
                form_data.append( 'san_imp_cust','yes' );
                     
                $.ajax({
                    url: ycsc.ajaxurl,
                    type: 'POST',
                    contentType: false,
                    processData: false,
                    data: form_data,
                    beforeSend:function(){
         
                        var sImg = '<span class="imp-cust-loading" style="padding-left:4px;"><img src="images/spinner-2x.gif" style="vertical-align:middle;width:18px;"></span>';
                        $( $this ).append( sImg );
                        $( $this ).attr( 'disabled','disabled' );
                        
                    },
                    success: function ( res ) {
                 
                    },
                    error: function( XMLHttpRequest, textStatus, errorThrown ) {
                        
                        alert( textStatus + ' : ' + errorThrown );
                        $( $this ).removeAttr( 'disabled' );
                        $( $this ).find( '.imp-cust-loading' ).remove();
                        
                    },
                    complete: function( res ) {
                        var rs = JSON.stringify( res.responseText );
                  
                        if( res.responseText && res.status == 200 ){
                            
                            if( custList.val() == '' || custList.length == 0 ){
                                
                                custList.val( res.responseText );
                                
                            } else {
                                
                                custList.val( custList.val() + "\n" + res.responseText + "\n" );
                                const newVal = custList.val().replace(/^\s*$(?:\r\n?|\n)/gm, "");
                                custList.val( newVal );
                            
                            }
                        }
                        
                        $( $this ).removeAttr( 'disabled' );
                        $( $this ).find( '.imp-cust-loading' ).remove();
                        
                       
                    }
                });
            }
            
            function kskAuto( ele ){
                $( ele ).show();
                setTimeout(function() { $( ele ).hide(); }, 4000);
            }
            
            $( '.wsm-import-cust' ).click( function( e ) {
            
                e.preventDefault();
                ycImport( '#san_wa_nums', this );
                
            
            });
       
            
            $( '.wsm-import-custn').click( function( e ) {
                 e.preventDefault();
                ycImport( '#wp_customer_listn', this );
            });
            
            $( '.wsm-send-wa' ).click( function( e ){
                e.preventDefault();
                
                var san_token       = $.trim( $( '#access_token' ).val() );
                var san_instance    = $.trim( $( '#instance_id' ).val() );
                var san_customers   = $.trim( $( '#wp_customer_listn' ).val());
                var san_msg         = $.trim( $( '#san-cu-wa-msg' ).val() );
                var san_cust_img    = $.trim( $( '#customer_msg_img').val() );
               
                var ksk         = $( '.san-table > tbody' ).find( '.san-wrcu' );
                var tdInfo      = $( ksk ).find('td').removeClass( 'san-res-info' );
                var sanArea     = $( '#san-wrap' );
                var form_data   = new FormData();
               
                $( ksk ).hide();

                if( san_token == '' || san_token.length == 0 ) {
                    
                    kskAuto( ksk );
                    $( tdInfo ).addClass( 'san-error' ).text( 'Access token missing!' );
                    return;
                    
                }
                
                else if( san_instance == '' || san_instance.length == 0 ){
                    
                    kskAuto( ksk );
                    $( tdInfo ).addClass( 'san-error' ).text( 'Instance ID missing!' );
                    return;
                    
                }
                
                else if( san_customers == '' || san_customers.length == 0 ){
                    
                    kskAuto( ksk );
                    $( tdInfo ).addClass( 'san-error' ).text( 'Please add customers!' );
                    return;
                    
                }
                
                else if( san_msg == '' || san_msg.length == 0 ){
                    
                    kskAuto( ksk );
                    $( tdInfo ).addClass( 'san-error' ).text( 'Please type message!' );
                    return;
                    
                }
                
                form_data.append( 'action', 'yc_send_customer_msg' );
                form_data.append( 'ycmsg', san_msg );
                form_data.append( 'yccustomers', san_customers );
                form_data.append( 'yccustimg', san_cust_img );
                
                sanProcessCustAjax( form_data,sanArea, ksk, tdInfo );

            });
            
            $( '.wsm-share-posts' ).click( function( e ){
                e.preventDefault();
                
                var san_token       = $.trim( $( '#access_token' ).val() );
                var san_instance    = $.trim( $( '#instance_id' ).val() );
                var san_nums        = $.trim( $( '#san_wa_nums' ).val() );
                var san_msg         = $.trim( $( '#san_wa_msg' ).val() );
                var san_inc_img     = $( '#san_inc_post_image' ).is( ':checked' ) ? 1 : '';
                var san_posts       = $( '#wp_posts_list' ).val();
                
                var ksk         = $( '.san-table > tbody' ).find( '.san-wrps' );
                var tdInfo      = $( ksk ).find('td').removeClass( 'san-res-info' );
                var sanArea     = $( '#san-wrap' );
                var form_data   = new FormData();
               
                $( ksk ).hide();
                   
                if( san_token == '' || san_token.length == 0 ) {
                    
                    kskAuto( ksk );
                    $( tdInfo ).addClass( 'san-error' ).text( 'Access token missing!' );
                    return;
                    
                }
                
                else if( san_instance == '' || san_instance.length == 0 ){
                    
                    kskAuto( ksk );
                    $( tdInfo ).addClass( 'san-error' ).text( 'Instance ID missing!' );
                    return;
                    
                }
                
                else if( san_nums == '' || san_nums.length == 0 ){
                    
                    kskAuto( ksk );
                    $( tdInfo ).addClass( 'san-error' ).text( 'Please provide numbers!' );
                    return;
                    
                }
                
                else if( san_msg == '' || san_msg.length == 0 ){
                    
                    kskAuto( ksk );
                    $( tdInfo ).addClass( 'san-error' ).text( 'Please type message!' );
                    return;
                    
                }
                
                else if( san_posts == '' || san_posts.length == 0 ){
                    kskAuto( ksk );
                    $( tdInfo ).addClass( 'san-error' ).text( 'Please select posts!' );
                    return;
                }
                
                form_data.append( 'action', 'yc_share_posts' );
                form_data.append( 'ycnums', san_nums );
                form_data.append( 'ycmsg', san_msg );
                form_data.append( 'ycimg', san_inc_img );
                form_data.append( 'ycposts', san_posts );
                
                $.ajax({
                    url: ycsc.ajaxurl,
                    type: 'POST',
                    contentType: false,
                    processData: false,
                    data: form_data,
                    beforeSend:function(){
                  
                         sanProcessingWA( sanArea );
                        
                    },
                    success: function ( res ) {
                 
                    },
                    error: function( XMLHttpRequest, textStatus, errorThrown ) {
                        
                        alert( textStatus + ' : ' + errorThrown );
                        $( sanArea ).find( '#san-overlay' ).remove();
                        
                    },
                    complete: function( res ) {
                    
                        $( sanArea ).find( '#san-overlay' ).remove();
                        $( ksk ).show();
                        setTimeout(function() { $( ksk ).hide(); }, 5000);
                        
                        if( res.responseText && res.status == 200 ){
                           
                            $( tdInfo ).addClass( 'san-res-info' ).text( 'Total '+res.responseText +' numbers(s) processed.' );
                            
                        }
                        
                        else if( res.responseText == '' && res.status == 200 ){
                           
                            $( tdInfo ).addClass( 'san-res-info' ).text( 'Please check number!' );
                            
                        }
                    }
                });
                

                
            });
            
            $( '.wsm-share-products' ).click( function( e ){
                e.preventDefault();
    
                var san_token       = $.trim( $( '#access_token' ).val() );
                var san_instance    = $.trim( $( '#instance_id' ).val() );
                var san_nums        = $.trim( $( '#san_wa_nums' ).val() );
                var san_msg         = $.trim( $( '#san_wa_msg' ).val() );
                var san_inc_img     = $( '#san_inc_post_image' ).is( ':checked' ) ? 1 : '';
                var san_products    = $( '#wp_products_list' ).val();
                
                var ksk         = $( '.san-table > tbody' ).find( '.san-wrpr' );
                var tdInfo      = $( ksk ).find( 'td' ).removeClass( 'san-res-info' );
                var $this       = $( this );
                var sanArea     = $( '#san-wrap' );
                var form_data   = new FormData();
                
                $( ksk ).hide();
                
                if( san_token == '' || san_token.length == 0 ) {
                    
                    kskAuto( ksk );
                    $('.san-error').text('Access token missing!');
                    return;
                }
                
                else if( san_instance == '' || san_instance.length == 0 ){
                    
                    kskAuto( ksk );
                    $('.san-error').text('Instance ID missing!');
                    return;
                }
                
                else if( san_nums == '' || san_nums.length == 0 ){
                    
                    kskAuto( ksk );
                    $('.san-error').text( 'Please provide numbers!' );
                    return;
                }
                
                else if( san_msg == '' || san_msg.length == 0 ){
                    
                    kskAuto( ksk );
                    $( '.san-error' ).text( 'Please type message!' );
                    return;
                }
                
                else if( san_products == '' || san_products.length == 0 ){
                    
                    kskAuto( ksk );
                    $( tdInfo ).addClass( 'san-error' ).text( 'Please select products!' );
                    return;
                    
                }
                
                form_data.append( 'action', 'yc_share_products' );
                form_data.append( 'ycnums', san_nums );
                form_data.append( 'ycmsg', san_msg );
                form_data.append( 'ycimg', san_inc_img );
                form_data.append( 'ycproducts', san_products );
                
                $.ajax({
                    url: ycsc.ajaxurl,
                    type: 'POST',
                    contentType: false,
                    processData: false,
                    data: form_data,
                    beforeSend:function(){

                       sanProcessingWA( sanArea );
                        
                    },
                    success: function ( res ) {
                  
                    },
                    error: function( XMLHttpRequest, textStatus, errorThrown ) {
                        
                        alert( textStatus + ' : ' + errorThrown );
                        $( sanArea ).find( '#san-overlay' ).remove();
                        
                    },
                    complete: function( res ) {

                        $( sanArea ).find( '#san-overlay' ).remove();
                        $( ksk ).show();
                        setTimeout(function() { $( ksk ).hide(); }, 5000);
                        
                        if( res.responseText && res.status == 200 ){
                           
                            $( tdInfo ).addClass( 'san-res-info' ).text( 'Total '+res.responseText +' numbers(s) processed.' );
                            
                        }
                        
                        else if( res.responseText == '' && res.status == 200 ){
                           
                            $( tdInfo ).addClass( 'san-res-info' ).text( 'Please check number!' );
                            
                        }
                        
                       
                    }
                });
   
            
            });
            
        });