<?php
	if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


	if (isset($_GET['generate_pages'])) {
		echo '<div class="updated"><p><strong>' . __('generati pagina', 'casawp-legal' ) . '</strong></p></div>';
		global $casawpLegal;
		$casawpLegal->makeSureTermsExists();
		$casawpLegal->makeSureImprintExists();
	}

	if (isset($_GET['generate_terms'])) {
		global $casawpLegal;
		$casawpLegal->makeSureTermsExists();
	}

	if (isset($_GET['generate_imprint'])) {
		global $casawpLegal;
		$casawpLegal->makeSureImprintExists();
	}

	if (isset($_GET['generate_footmenu_items'])) {
		echo '<div class="updated"><p><strong>' . __('generati menutia itemito', 'casawp-legal' ) . '</strong></p></div>';
		global $casawpLegal;
		$casawpLegal->addToFootmenu();
	}

	if (isset($_POST['casawp_fetchdata_from_casaauth_admin'])) {
		echo '<div class="updated"><p><strong>' . __('fetchito dati a la casaautheto', 'casawp-legal' ) . '</strong></p></div>';
		$gateway_private_key = $_POST['casawp_gateway_private_key'];
		$gateway_public_key = $_POST['casawp_gateway_public_key'];
		$force = (isset($_POST['force']) && $_POST['force'] ? true : false);
		global $casawpLegal;
		$transcript = $casawpLegal->fetchCompanyDataFromGateway($gateway_private_key, $gateway_public_key, $force);
	}

	if(isset($_POST['casawp_legal_submit'])) {
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
					'company_vat',
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
			'general'     => 'Allgemein',
			'person'      => 'Datenschutzbeauftragter',
			'actions'     => 'Aktionen',
		);
	    echo screen_icon('options-general');
	    echo '<h2 class="nav-tab-wrapper">';
	    // echo '<div style="float:right;">
	    //     <a href="http://wordpress.org/support/view/plugin-reviews/casawp" target="_blank" class="add-new-h2">Rate this plugin</a>
	    //     <a href="http://wordpress.org/plugins/casawp/changelog/" target="_blank" class="add-new-h2">Changelog</a>
	    // </div>';
	    $current = isset($_GET['tab']) ? $_GET['tab'] : 'general';
	    foreach( $tabs as $tab => $name ){
	        $class = ( $tab == $current ) ? ' nav-tab-active' : '';
	        echo "<a class='nav-tab$class' href='?page=casawp_legal&tab=$tab'>$name</a>";

	    }
	    echo '</h2>';
	?>


	<form action="" method="post" id="options_form" name="options_form">
		<?php
			$table_start = '<table class="form-table"><tbody>';
			$table_end   = '</tbody></table>';
			switch ($current) {
				case 'actions': ?>
					<?php echo $table_start; ?>
					<tr valign="top">
						<th scope="row">
							Impressum
							<?php if (get_option('casawp_legal_imprint', false)) : ?>
								<br><small>✔ Seite vorhanden</small>
							<?php endif ?>
						</th>
						<td>
							<a href="?page=casawp_legal&tab=actions&generate_imprint" class="button-primary">Seite erstellen</a>
						</td>
					</tr>
					<tr><th><hr></th><td><hr></td></tr>
					<tr valign="top">
						<th scope="row">
							Datenschutz
							<?php if (get_option('casawp_legal_terms', false)) : ?>
								<br><small>✔ Seite vorhanden</small>
							<?php endif ?>
						</th>
						<td>
							<a href="?page=casawp_legal&tab=actions&generate_terms" class="button-primary">Seite erstellen</a>
						</td>
					</tr>
					<tr><th><hr></th><td><hr></td></tr>
					<tr valign="top">
						<th scope="row">
							Seite im Menü hinzufügen
							<?php 
								$location = 'footmenu';
							    $locations = get_nav_menu_locations();
							    $menu_id = (array_key_exists($location, $locations) ?  $locations[ $location ] : false);
							    $menu_exists = false;
							    if ($menu_id) {
							      $menu_exists = wp_get_nav_menu_object($menu_id);
							    }
							?>
							<?php if ($menu_exists) : ?>
								<br><small>✔ Menu verhanden</small>
							<?php endif ?>
						</th>
						<td>
							<a href="?page=casawp_legal&tab=actions&generate_footmenu_items" class="button-primary">Automatisch hinzufügen</a>
							<br><small>Versucht für den Menubereich "footmenu" ein Menu zu erstellen und fügt die Seiten da hinzu.</small>
							<?php 
								if (function_exists('icl_object_id')) {
						          echo '<br><small><strong>WPML Plugin entdeckt! Hier kannst du das Menu mit den Sprachen abgleichen: <a href="/wp-admin/admin.php?page=sitepress-multilingual-cms%2Fmenu%2Fmenu-sync%2Fmenus-sync.php">Link</a></strong></small>';
						        }
							?>
						</td>
					</tr>
					<tr><th><hr></th><td><hr></td></tr>
					<tr valign="top">
						<th scope="row">
							CASAAUTH Import
						</th>
						<td>
							<label>CASAGATEWAY provider API Public Key</label>
							<br><input style="width: 100%" type="text" name="casawp_gateway_public_key" value="<?= (isset($_POST['casawp_gateway_public_key']) ? $_POST['casawp_gateway_public_key'] : ''); ?>" placeholder="CASAGATEWAY provider API Public Key" />
							<br>
							<br>
							<label>CASAGATEWAY provider API Private Key</label>
							<br><input style="width: 100%" type="text" name="casawp_gateway_private_key" value="<?= (isset($_POST['casawp_gateway_private_key']) ? $_POST['casawp_gateway_private_key'] : ''); ?>" placeholder="CASAGATEWAY provider API Private Key" />
							<br>
							<br><input type="checkbox" name="force" value="<?= (isset($_POST['force']) ? $_POST['force'] : ''); ?>" /> Daten-Felder überschreiben
							<br>
							<br><input type="submit" name="casawp_fetchdata_from_casaauth_admin" id="submit" class="button button-primary" value="Importieren">
						</td>
					</tr>
					<tr><th><hr></th><td><hr></td></tr>
					<?php echo $table_end; ?>
					<?php break;
				case 'person': ?>
					<?php /******* Person *******/ ?>
						<?php echo $table_start; ?>
							
						<?php 
							$prefix = 'casawp_legal_';
							$fields = array();
							$fields[] = [
								'name' => $prefix.'company_person_first_name',
								'label' => 'Vorname',
								'placeholder' => '',
								'type' => 'text',
								'intructions' => '',
							];
							$fields[] = [
								'name' => $prefix.'company_person_last_name',
								'label' => 'Nachname',
								'placeholder' => '',
								'type' => 'text',
								'intructions' => '',
							];
							$fields[] = [
								'name' => $prefix.'company_person_email',
								'label' => 'E-Mail',
								'placeholder' => '',
								'type' => 'text',
								'intructions' => '',
							];

							foreach ($fields as $field) : ?>
								<?php $name = $field['name']; ?>
								<?php $text = $field['label']; ?>
								<tr valign="top">
									<th scope="row"><?= $field['label'] ?></th>
									<td>
										<?php if ($field['type'] === 'text'): ?>
											<fieldset>
												<legend class="screen-reader-text"><span><?= $text ?></span></legend>
												<p>
													<input type="text" placeholder="<?= $field['placeholder'] ?>" name="<?php echo $name ?>" value="<?= get_option($name) ?>" id="<?php echo $name; ?>" class="large-text" />
												</p>
											</fieldset>
										<?php elseif ($field['type'] === 'bool'): ?>
											<fieldset>
												<legend class="screen-reader-text"><span><?= $text ?></span></legend>
												<label>
													<input name="<?php echo $name ?>" type="checkbox" value="1" <?php echo (get_option($name) == '1' ? 'checked="checked"' : ''); ?>> <?php echo $field['intructions'] ?>
												</label>
										<?php endif ?>
									</td>
								</tr>
							<?php endforeach ?>


						<?php echo $table_end; ?>
						<p class="submit"><input type="submit" name="casawp_legal_submit" id="submit" class="button button-primary" value="Änderungen übernehmen"></p>
					<?php break;
				case 'general':
				default:
					?>
						<?php /******* General *******/ ?>
						<?php echo $table_start; ?>
							<tr valign="top">
								<th scope="row">Impressum</th>
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
								<th scope="row">Datenschutz</th>
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
							$fields[] = [
								'name' => $prefix.'company_legal_name',
								'label' => 'Firma / Organisation',
								'placeholder' => '',
								'type' => 'text',
								'intructions' => '',
							];
							$fields[] = [
								'name' => $prefix.'company_phone',
								'label' => 'Telefon',
								'placeholder' => '',
								'type' => 'text',
								'intructions' => '',
							];
							$fields[] = [
								'name' => $prefix.'company_fax',
								'label' => 'Fax',
								'placeholder' => '',
								'type' => 'text',
								'intructions' => '',
							];
							$fields[] = [
								'name' => $prefix.'company_email',
								'label' => 'E-Mail',
								'placeholder' => '',
								'type' => 'text',
								'intructions' => '',
							];
							$fields[] = [
								'name' => $prefix.'company_website_url',
								'label' => 'Webseite',
								'placeholder' => '',
								'type' => 'text',
								'intructions' => '',
							];
							$fields[] = [
								'name' => $prefix.'company_uid',
								'label' => 'UID',
								'placeholder' => 'CHE-',
								'type' => 'text',
								'intructions' => '',
							];
							$fields[] = [
								'name' => $prefix.'company_vat',
								'label' => 'Mehrwertsteuerpflicht',
								'placeholder' => '',
								'type' => 'bool',
								'intructions' => 'Ja',
							];
							$fields[] = [
								'name' => $prefix.'company_address_street',
								'label' => 'Strasse',
								'placeholder' => '',
								'type' => 'text',
								'intructions' => '',
							];
							$fields[] = [
								'name' => $prefix.'company_address_street_number',
								'label' => 'Nr.',
								'placeholder' => '',
								'type' => 'text',
								'intructions' => '',
							];
							$fields[] = [
								'name' => $prefix.'company_address_post_office_box_number',
								'label' => 'Postfach',
								'placeholder' => '',
								'type' => 'text',
								'intructions' => '',
							];
							$fields[] = [
								'name' => $prefix.'company_address_postal_code',
								'label' => 'PLZ',
								'placeholder' => '',
								'type' => 'text',
								'intructions' => '',
							];
							$fields[] = [
								'name' => $prefix.'company_address_locality',
								'label' => 'Ort',
								'placeholder' => '',
								'type' => 'text',
								'intructions' => '',
							];
							$fields[] = [
								'name' => $prefix.'company_address_country',
								'label' => 'Land',
								'placeholder' => '',
								'type' => 'text',
								'intructions' => '',
							];

							foreach ($fields as $field) : ?>
								<?php $name = $field['name']; ?>
								<?php $text = $field['label']; ?>
								<tr valign="top">
									<th scope="row"><?= $field['label'] ?></th>
									<td>
										<?php if ($field['type'] === 'text'): ?>
											<fieldset>
												<legend class="screen-reader-text"><span><?= $text ?></span></legend>
												<p>
													<input type="text" placeholder="<?= $field['placeholder'] ?>" name="<?php echo $name ?>" value="<?= get_option($name) ?>" id="<?php echo $name; ?>" class="large-text" />
												</p>
											</fieldset>
										<?php elseif ($field['type'] === 'bool'): ?>
											<fieldset>
												<legend class="screen-reader-text"><span><?= $text ?></span></legend>
												<label>
													<input name="<?php echo $name ?>" type="checkbox" value="1" <?php echo (get_option($name) == '1' ? 'checked="checked"' : ''); ?>> <?php echo $field['intructions'] ?>
												</label>
										<?php endif ?>
									</td>
								</tr>
							<?php endforeach ?>


						<?php echo $table_end; ?>
						<p class="submit"><input type="submit" name="casawp_legal_submit" id="submit" class="button button-primary" value="Änderungen übernehmen"></p>
					<?php
					break;
			}
		?>
	</form>
