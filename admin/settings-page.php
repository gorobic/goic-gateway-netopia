<?php
add_action('admin_menu', 'ggn_setup_menu');
add_action('admin_init', 'ggn_settings_init' );
 
function ggn_setup_menu(){
    add_menu_page( 'Netopia Payment', 'Netopia Payment', 'manage_options', 'ggn_options', 'ggn_options_page' );
}

function ggn_settings_init(  ) { 

	register_setting( 'pluginPage', 'ggn_settings' );

	add_settings_section(
		'ggn_pluginPage_section', 
		__( 'Payment method settings', 'goic-gateway-netopia' ), 
		'ggn_settings_section_callback', 
		'pluginPage'
	);

	add_settings_field( 
		'ggn_test_mode', 
		__( 'Test mode', 'goic-gateway-netopia' ), 
		'ggn_test_mode_render', 
		'pluginPage', 
		'ggn_pluginPage_section' 
	);

	add_settings_field( 
		'ggn_signature', 
		__( 'Signature', 'goic-gateway-netopia' ), 
		'ggn_signature_render', 
		'pluginPage', 
		'ggn_pluginPage_section' 
	);

	add_settings_field( 
		'ggn_title', 
		__( 'Title', 'goic-gateway-netopia' ), 
		'ggn_title_render', 
		'pluginPage', 
		'ggn_pluginPage_section' 
	);

	add_settings_field( 
		'ggn_description', 
		__( 'Description', 'goic-gateway-netopia' ), 
		'ggn_description_render', 
		'pluginPage', 
		'ggn_pluginPage_section' 
	);


}


function ggn_test_mode_render(  ) { 

	$options = get_option( 'ggn_settings' );
	?>
	<input type='checkbox' name='ggn_settings[ggn_test_mode]' <?php checked( $options['ggn_test_mode'], 1 ); ?> value='1'>
	<?php

}


function ggn_signature_render(  ) { 

	$options = get_option( 'ggn_settings' );
	?>
	<input type='text' name='ggn_settings[ggn_signature]' value='<?php echo $options['ggn_signature']; ?>'>
	<?php

}


function ggn_title_render(  ) { 
    global $default_title;
	$options = get_option( 'ggn_settings' );
	?>
	<input type='text' name='ggn_settings[ggn_title]' value='<?php echo ($options['ggn_title']) ? $options['ggn_title'] : $default_title; ?>'>
	<?php

}


function ggn_description_render(  ) { 
    global $default_description;
	$options = get_option( 'ggn_settings' );
	?>
	<textarea cols='40' rows='5' name='ggn_settings[ggn_description]'><?php 
		echo ($options['ggn_description']) ? $options['ggn_description'] : $default_description; ?>
 	</textarea>
	<?php

}


function ggn_settings_section_callback(  ) { 

	echo __( 'Upload your certificates to "/wp-content/ggn/certificates" folder. You need to create this folder.', 'goic-gateway-netopia' );

}


function ggn_options_page(  ) { 

		?>
		<form action='options.php' method='post'>

            <h2><?php _e('MobilPay Gateway', 'goic-gateway-netopia'); ?></h2>
            
			<?php
			settings_fields( 'pluginPage' );
			do_settings_sections( 'pluginPage' );
			submit_button();
			?>

		</form>
		<?php

}
