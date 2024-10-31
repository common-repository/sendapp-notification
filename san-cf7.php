<div>
    <h3><?php _e( 'WhatsApp Notification', 'sendapp-wb' ); ?></h3>
    <fieldset>
        <legend><?php _e('Send WhatsApp message to specific number after submit form<br>These are following tags you can use:', 'sendapp-wb'); ?><br><?php $san_options['form']->suggest_mail_tags(); ?></legend>
        <table class="form-table">
            <tbody>
            <tr>
                <th scope="row"><label for="san-wa-sender"><?php _e( 'To:', 'sendapp-wb' ); ?></label></th>
                <td>
                <input type="text" value="<?php echo esc_attr($san_options['phone']); ?>" size="70" class="large-text code" name="san-wa[phone]" id="san-wa-sender">
                    <p class="description"><?php _e( 'Add country code without (+) sign before number', 'sendapp-wb' ); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="san-wa-message"><?php _e( 'Message:', 'sendapp-wb' ); ?></label></th>
                <td>
                <textarea class="large-text" rows="4" cols="100" name="san-wa[message]" id="san-wa-message"><?php echo esc_textarea($san_options['message']); ?></textarea>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><label for="san-wa-image"><?php _e( 'Image Attachment:', 'sendapp-wb' ); ?></label></th>
                <td>
                <input type="text" value="<?php echo esc_attr($san_options['image']); ?>" size="70" class="large-text code" name="san-wa[image]" id="san-wa-image">
                    <p class="description"><?php _e( 'Insert image url to attach into message (Max 1 MB)', 'sendapp-wb' ); ?></p>
                </td>
            </tr>
        </tbody></table>
    </fieldset>
</div>