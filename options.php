<?php
	if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

	if(isset($_POST['casawp_legal_submit'])) {
		print_r($_POST);
		foreach ($_POST AS $key => $value) {
			$value = sanitize_text_field($value);
			if (substr($key, 0, 12) == 'casawp_legal') {
				update_option( $key, $value );
			}
		}

		$current = isset($_GET['tab']) ? $_GET['tab'] : 'general';
		switch ($current) {
			case 'general':
			default:
				$checkbox_traps = array(
					'casawp_legal_testoption',
				);
				break;
		}

		//reset

		foreach ($checkbox_traps as $trap) {
			if (!isset($_POST[$trap])) {
				update_option( $trap, '0' );
			}
		}
		echo '<div class="updated"><p><strong>' . __('Einstellungen gespeichert..', 'casawp-legal' ) . '</strong></p></div>';
	}
?>


<div class="wrap">
	<h1><strong>CASA</strong><span style="font-weight:100">WP</span> Legal</h1>
	<?php
		// Tabs
		$tabs = array(
			'general'     => 'Generell',
		);
	    echo screen_icon('options-general');
	    echo '<h2 class="nav-tab-wrapper">';
	    echo '<div style="float:right;">
	        <a href="http://wordpress.org/support/view/plugin-reviews/casawp" target="_blank" class="add-new-h2">Rate this plugin</a>
	        <a href="http://wordpress.org/plugins/casawp/changelog/" target="_blank" class="add-new-h2">Changelog</a>
	    </div>';
	    $current = isset($_GET['tab']) ? $_GET['tab'] : 'general';
	    foreach( $tabs as $tab => $name ){
	        $class = ( $tab == $current ) ? ' nav-tab-active' : '';
	        echo "<a class='nav-tab$class' href='?page=casawp&tab=$tab'>$name</a>";

	    }
	    echo '</h2>';
	?>


	<form action="" method="post" id="options_form" name="options_form">
		<?php
			$table_start = '<table class="form-table"><tbody>';
			$table_end   = '</tbody></table>';
			switch ($current) {
				case 'general':
				default:
					?>
						<?php /******* General *******/ ?>
						<?php echo $table_start; ?>
							<tr valign="top">
								<th scope="row">Impressum Seite</th>
								<td>
									<fieldset>
										<?php $name = 'casawp_legal_imprint'; ?>
										<?php $args = array(
											 'selected'              => get_option($name),
											 'echo'                  => 1,
											 'name'                  => $name,
											 'show_option_none'      => 'Auswählen',
											 'option_none_value'     => null,
											);
											wp_dropdown_pages( $args );
										?>
									</fieldset>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row">Datenschutz Seite</th>
								<td>
									<fieldset>
										<?php $name = 'casawp_legal_terms'; ?>
										<?php $args = array(
											 'selected'              => get_option($name),
											 'echo'                  => 1,
											 'name'                  => $name,
											 'show_option_none'      => 'Auswählen',
											 'option_none_value'     => null,
											);
											wp_dropdown_pages( $args );
										?>
									</fieldset>
								</td>
							</tr>
	

						<?php 
							$prefix = 'casawp_legal_';
							$fields = array();
							$fields[] = $prefix.'company_legal_name';
							$fields[] = $prefix.'company_phone';
							$fields[] = $prefix.'company_fax';
							$fields[] = $prefix.'company_email';
							$fields[] = $prefix.'company_uid';
							$fields[] = $prefix.'company_vat';
							$fields[] = $prefix.'company_address_street';
							$fields[] = $prefix.'company_address_street_number';
							$fields[] = $prefix.'company_address_post_office_box_number';
							$fields[] = $prefix.'company_address_postal_code';
							$fields[] = $prefix.'company_address_locality';
							foreach ($fields as $field) : ?>
								<tr valign="top">
									<th scope="row"><?= $field ?></th>
									<td>
										<fieldset>
											<?php $name = $field; ?>
											<?php $text = $field; ?>
											<legend class="screen-reader-text"><span><?= $text ?></span></legend>
											<p>
												<input type="text" placeholder="" name="<?php echo $name ?>" value="<?= get_option($name) ?>" id="<?php echo $name; ?>" class="large-text" />
											</p>
										</fieldset>
									</td>
								</tr>
							<?php endforeach ?>


						<?php echo $table_end; ?>
					<?php
					break;
			}
		?>
		<p class="submit"><input type="submit" name="casawp_legal_submit" id="submit" class="button button-primary" value="Änderungen übernehmen"></p>
	</form>
