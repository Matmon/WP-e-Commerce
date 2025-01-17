<?php

function wpsc_display_coupons_page() {
	global $wpdb;
	if ( isset( $_POST ) && is_array( $_POST ) && !empty( $_POST ) ) {

		if ( isset( $_POST['add_coupon'] ) && ($_POST['add_coupon'] == 'true') && (!isset( $_POST['is_edit_coupon'] ) || !($_POST['is_edit_coupon'] == 'true')) ) {
			
			$coupon_code   = $_POST['add_coupon_code'];
			$discount      = (double)$_POST['add_discount'];
			$discount_type = (int)$_POST['add_discount_type'];
			$free_shipping_details = serialize( (array)$_POST['free_shipping_options'] );
			$use_once      = (int)(bool)$_POST['add_use-once'];
			$every_product = (int)(bool)$_POST['add_every_product'];
			$is_active     = (int)(bool)$_POST['add_active'];
			$use_x_times   = (int)$_POST['add_use-x-times'];
			$start_date    = date( 'Y-m-d', strtotime( $_POST['add_start'] ) ) . " 00:00:00";
			$end_date      = date( 'Y-m-d', strtotime( $_POST['add_end'] ) ) . " 00:00:00";
			$rules         = $_POST['rules'];

			foreach ( $rules as $key => $rule ) {
				foreach ( $rule as $k => $r ) {
					$new_rule[$k][$key] = $r;
				}
			}

			foreach ( $new_rule as $key => $rule ) {
				if ( '' == $rule['value'] ) {
					unset( $new_rule[$key] );
				}
			}

			if ( $wpdb->query( "INSERT INTO `" . WPSC_TABLE_COUPON_CODES . "` ( `coupon_code` , `value` , `is-percentage` , `use-once` , `use-x-times`,`free-shipping`,`is-used` , `active` , `every_product` , `start` , `expiry`, `condition` ) VALUES ( '$coupon_code', '$discount', '$discount_type', '$use_once', '$use_x_times','$free_shipping_details', '0', '$is_active', '$every_product', '$start_date' , '$end_date' , '" . serialize( $new_rule ) . "' );" ) )
				echo "<div class='updated'><p align='center'>" . __( 'Thanks, the coupon has been added.', 'wpsc' ) . "</p></div>";

		}

		if ( isset( $_POST['is_edit_coupon'] ) && ($_POST['is_edit_coupon'] == 'true') && !(isset( $_POST['delete_condition'] )) && !(isset( $_POST['submit_condition'] )) ) {

			foreach ( (array)$_POST['edit_coupon'] as $coupon_id => $coupon_data ) {

				$coupon_id             = (int)$coupon_id;
				$coupon_data['start']  = $coupon_data['start'] . " 00:00:00";
				$coupon_data['expiry'] = $coupon_data['expiry'] . " 00:00:00";
				$check_values          = $wpdb->get_row( "SELECT `id`, `coupon_code`, `value`, `is-percentage`, `use-once`, `use-x-times`, `active`, `start`, `expiry`,`every_product` FROM `" . WPSC_TABLE_COUPON_CODES . "` WHERE `id` = '$coupon_id'", ARRAY_A );

				// Sort both arrays to make sure that if they contain the same stuff,
				// that they will compare to be the same, may not need to do this, but what the heck
				if ( $check_values != null )
					ksort( $check_values );

				ksort( $coupon_data );

				if ( $check_values != $coupon_data ) {

					$insert_array = array();

					foreach ( $coupon_data as $coupon_key => $coupon_value ) {
						if ( ($coupon_key == "submit_coupon") || ($coupon_key == "delete_coupon") )
							continue;

						if ( isset( $check_values[$coupon_key] ) && $coupon_value != $check_values[$coupon_key] )
							$insert_array[] = "`$coupon_key` = '$coupon_value'";

					}

					if ( isset( $check_values['every_product'] ) && $coupon_data['add_every_product'] != $check_values['every_product'] )
						$insert_array[] = "`every_product` = '$coupon_data[add_every_product]'";

					if ( count( $insert_array ) > 0 )
						$wpdb->query( "UPDATE `" . WPSC_TABLE_COUPON_CODES . "` SET " . implode( ", ", $insert_array ) . " WHERE `id` = '$coupon_id' LIMIT 1;" );

					unset( $insert_array );
					$rules = $_POST['rules'];

					foreach ( (array)$rules as $key => $rule ) {
						foreach ( $rule as $k => $r ) {
							$new_rule[$k][$key] = $r;
						}
					}

					foreach ( (array)$new_rule as $key => $rule ) {
						if ( $rule['value'] == '' ) {
							unset( $new_rule[$key] );
						}
					}

					$conditions = $wpdb->get_var( "SELECT `condition` FROM `" . WPSC_TABLE_COUPON_CODES . "` WHERE `id` = '" . (int)$_POST['coupon_id'] . "' LIMIT 1" );
					$conditions = unserialize( $conditions );
					$new_cond = array();

					if ( $_POST['rules']['value'][0] != '' ) {
						$new_cond['property'] = $_POST['rules']['property'][0];
						$new_cond['logic'] = $_POST['rules']['logic'][0];
						$new_cond['value'] = $_POST['rules']['value'][0];
						$conditions [] = $new_cond;
					}

					$sql = "UPDATE `" . WPSC_TABLE_COUPON_CODES . "` SET `condition`='" . serialize( $conditions ) . "' WHERE `id` = '" . (int)$_POST['coupon_id'] . "' LIMIT 1";
					$wpdb->query( $sql );
				}
			}
		}

		if ( isset( $_POST['delete_condition'] ) ) {

			$conditions = $wpdb->get_var( "SELECT `condition` FROM `" . WPSC_TABLE_COUPON_CODES . "` WHERE `id` = '" . (int)$_POST['coupon_id'] . "' LIMIT 1" );
			$conditions = unserialize( $conditions );

			unset( $conditions[(int)$_POST['delete_condition']] );

			$sql = "UPDATE `" . WPSC_TABLE_COUPON_CODES . "` SET `condition`='" . serialize( $conditions ) . "' WHERE `id` = '" . (int)$_POST['coupon_id'] . "' LIMIT 1";
			$wpdb->query( $sql );
		}

		if ( isset( $_POST['submit_condition'] ) ) {
			$conditions = $wpdb->get_var( "SELECT `condition` FROM `" . WPSC_TABLE_COUPON_CODES . "` WHERE `id` = '" . (int)$_POST['coupon_id'] . "' LIMIT 1" );
			$conditions = unserialize( $conditions );

			$new_cond             = array();
			$new_cond['property'] = $_POST['rules']['property'][0];
			$new_cond['logic']    = $_POST['rules']['logic'][0];
			$new_cond['value']    = $_POST['rules']['value'][0];
			$conditions[]         = $new_cond;

			$sql = "UPDATE `" . WPSC_TABLE_COUPON_CODES . "` SET `condition`='" . serialize( $conditions ) . "' WHERE `id` = '" . (int)$_POST['coupon_id'] . "' LIMIT 1";
			$wpdb->query( $sql );
		}
	} ?>

	<script type='text/javascript'>
		jQuery(".pickdate").datepicker();
		/* jQuery datepicker selector */
		if (typeof jQuery('.pickdate').datepicker != "undefined") {
			jQuery('.pickdate').datepicker({ dateFormat: 'yy-mm-dd' });
		}
	</script>

	<div class="wrap">
		<h2>
			<?php _e( 'Coupons', 'wpsc' ); ?>
			<a href="#" id="add_coupon_box_link" class="add_item_link button add-new-h2" onClick="return show_status_box( 'add_coupon_box', 'add_coupon_box_link' );">
				<?php _e( 'Add New', 'wpsc' ); ?>
			</a>
		</h2>
		
		<table style="width: 100%;">
			<tr>
				<td id="coupon_data">
					<div id='add_coupon_box' class='modify_coupon' >
						<form name='add_coupon' method='post' action=''>
							<table class='add-coupon' >
								<tr>
									<th><?php _e( 'Coupon Code', 'wpsc' ); ?></th>
									<th><?php _e( 'Discount', 'wpsc' ); ?></th>
									<th id="free_shipping_options_tr" style="display:none;"><?php _e( 'Select a Country/Region', 'wpsc' ); ?></th>
									<th><?php _e( 'Start', 'wpsc' ); ?></th>
									<th><?php _e( 'Expiry', 'wpsc' ); ?></th>
								</tr>
								<tr>
									<td>
										<input type='text' value='' name='add_coupon_code' />
									</td>
									<td>
										<input type='text' value='' size='3' name='add_discount' id='add_discount'  />
										<select name='add_discount_type' id='add_discount_type' onchange = 'show_shipping_options();'>
											<option value='0' >$</option>
											<option value='1' >%</option>
											<option value='2' ><?php _e( 'Free shipping', 'wpsc' ); ?></option>
										</select>
									</td>
									<td id="free_shipping_options" style="display:none;">
								
										<select name='free_shipping_options[discount_country]' id='coupon_country_list' onchange='show_region_list();'>
											<option value='' >All Countries and Regions</option>
											<?php echo country_list(); ?>
										</select>
										
										<span id='discount_options_country'>
										<?php
										//i dont think we need this cu we need to do an ajax request to generate this list 
										//based on the country chosen probably need the span place holder tho
										$region_list = $wpdb->get_results( "SELECT `" . WPSC_TABLE_REGION_TAX . "`.* FROM `" . WPSC_TABLE_REGION_TAX . "`, `" . WPSC_TABLE_CURRENCY_LIST . "`  WHERE `" . WPSC_TABLE_CURRENCY_LIST . "`.`isocode` IN('" . esc_attr( get_option( $free_shipping_country ) ) . "') AND `" . WPSC_TABLE_CURRENCY_LIST . "`.`id` = `" . WPSC_TABLE_REGION_TAX . "`.`country_id`", ARRAY_A );
										if ( !empty( $region_list ) ) { ?>

											<select name='free_shipping_options[discount_region]'>
											<?php
												foreach ( $region_list as $region ) {
													if ( esc_attr( $free_shipping_region ) == $region['id'] ) {
														$selected = "selected='selected'";
													} else {
														$selected = "";
													}
												?>
												<option value='<?php echo $region['id']; ?>' <?php echo $selected; ?> ><?php echo esc_attr( $region['name'] ); ?></option> <?php
												}
											?>
											</select>	
									<?php } ?>
									</span>
									
									</td>
									<td>
										<input type='text' class='pickdate' size='11' value="<?php echo date('Y-m-d'); ?>" name='add_start' />
									</td>
									<td>
										<input type='text' class='pickdate' size='11' name='add_end' value="<?php echo (date('Y')+1) . date('-m-d') ; ?>">
									</td>
									<td>
										<input type='hidden' value='true' name='add_coupon' />
										<input type='submit' value='Add Coupon' name='submit_coupon' class='button-primary' />
									</td>
								</tr>

								<tr>
									<td colspan='3' scope="row">
										<p>
											<span class='input_label'><?php _e( 'Active', 'wpsc' ); ?></span><input type='hidden' value='0' name='add_active' />
											<input type='checkbox' value='1' checked='checked' name='add_active' />
											<span class='description'><?php _e( 'Activate coupon on creation.', 'wpsc' ) ?></span>
										</p>
									</td>
								</tr>

								<tr>
									<td colspan='3' scope="row">
										<p>
											<span class='input_label'><?php _e( 'Use Once', 'wpsc' ); ?></span><input type='hidden' value='0' name='add_use-once' />
											<input type='checkbox' value='1' name='add_use-once' />
											<span class='description'><?php _e( 'Deactivate coupon after it has been used.', 'wpsc' ) ?></span>
										</p>
									</td>
								</tr>
								
								<tr>
									<td colspan='3' scope="row">
										<p>
											<span class='input_label'><?php _e( 'Limited Number', 'wpsc' ); ?></span><input type='hidden' value='0' name='add_use-x-times' />
											<input type='text' size='4' value='' name='add_use-x-times' />
											<span class='description'><?php _e( 'Set the amount of times the coupon can be used.', 'wpsc' ) ?></span>
										</p>
									</td>
								</tr>

								<tr>
									<td colspan='3' scope="row">
										<p>
											<span class='input_label'><?php _e( 'Apply On All Products', 'wpsc' ); ?></span><input type='hidden' value='0' name='add_every_product' />
											<input type="checkbox" value="1" name='add_every_product'/>
											<span class='description'><?php _e( 'This coupon affects each product at checkout.', 'wpsc' ) ?></span>
										</p>
									</td>
								</tr>

								<tr><td colspan='3'><span id='table_header'>Conditions</span></td></tr>
								<tr>
									<td colspan="8">
									<div class='coupon_condition' >
										<div class='first_condition'>
											<select class="ruleprops" name="rules[property][]">
												<option value="item_name" rel="order"><?php _e( 'Item name', 'wpsc' ); ?></option>
												<option value="item_quantity" rel="order"><?php _e( 'Item quantity', 'wpsc' ); ?></option>
												<option value="total_quantity" rel="order"><?php _e( 'Total quantity', 'wpsc' ); ?></option>
												<option value="subtotal_amount" rel="order"><?php _e( 'Subtotal amount', 'wpsc' ); ?></option>
												<?php echo apply_filters( 'wpsc_coupon_rule_property_options', '' ); ?>
											</select>

											<select name="rules[logic][]">
												<option value="equal"><?php _e( 'Is equal to', 'wpsc' ); ?></option>
												<option value="greater"><?php _e( 'Is greater than', 'wpsc' ); ?></option>
												<option value="less"><?php _e( 'Is less than', 'wpsc' ); ?></option>
												<option value="contains"><?php _e( 'Contains', 'wpsc' ); ?></option>
												<option value="not_contain"><?php _e( 'Does not contain', 'wpsc' ); ?></option>
												<option value="begins"><?php _e( 'Begins with', 'wpsc' ); ?></option>
												<option value="ends"><?php _e( 'Ends with', 'wpsc' ); ?></option>
												<option value="category"><?php _e( 'In Category', 'wpsc' ); ?></option>
											</select>

											<span><input type="text" name="rules[value][]"/></span>

											<span>
												<script>
													var coupon_number=1;
													function add_another_property(this_button){
														var new_property='<div class="coupon_condition">\n'+
															'<div><img height="16" width="16" class="delete" alt="Delete" src="<?php echo WPSC_CORE_IMAGES_URL; ?>/cross.png" onclick="jQuery(this).parent().remove();"/> \n'+
															'<select class="ruleprops" name="rules[property][]"> \n'+
															'<option value="item_name" rel="order">Item name</option> \n'+
															'<option value="item_quantity" rel="order">Item quantity</option>\n'+
															'<option value="total_quantity" rel="order">Total quantity</option>\n'+
															'<option value="subtotal_amount" rel="order">Subtotal amount</option>\n'+
															'<?php echo apply_filters( 'wpsc_coupon_rule_property_options', '' ); ?>'+
															'</select> \n'+
															'<select name="rules[logic][]"> \n'+
															'<option value="equal">Is equal to</option> \n'+
															'<option value="greater">Is greater than</option> \n'+
															'<option value="less">Is less than</option> \n'+
															'<option value="contains">Contains</option> \n'+
															'<option value="not_contain">Does not contain</option> \n'+
															'<option value="begins">Begins with</option> \n'+
															'<option value="ends">Ends with</option> \n'+
															'</select> \n'+
															'<span> \n'+
															'<input type="text" name="rules[value][]"/> \n'+
															'</span>  \n'+
															'</div> \n'+
															'</div> ';
		
														jQuery('.coupon_condition :first').after(new_property);
														coupon_number++;
													}
													
													//displays the free shipping options
													function show_shipping_options() {
														var discount_type = document.getElementById("add_discount_type").value;
														if (discount_type == "2") {
															document.getElementById("free_shipping_options_tr").style.display='block';
															document.getElementById("free_shipping_options").style.display='block';
															document.getElementById("add_discount").style.display='none';
														}else{
															document.getElementById("free_shipping_options_tr").style.display='none';
															document.getElementById("free_shipping_options").style.display='none';
															document.getElementById("add_discount").style.display='inline';		
														}
													}
												
												//need to send the selected country off via ajax to return the region select box for that country
												function show_region_list(){
													var country_id = document.getElementById("coupon_country_list").value;
												}


												</script>
											</span>
										</div>
									</div>
								</tr>

								<tr>
									<td>
										<a class="wpsc_coupons_condition_add" onclick="add_another_property(jQuery(this));">
											<?php _e( 'Add New Condition', 'wpsc' ); ?>
										</a>
									</td>
								</tr>
							</table>
						</form>
					</div>
				</td>
			</tr>
		</table>

		<?php
			$columns = array(
				'coupon_code' => __( 'Coupon Code', 'wpsc' ),
				'discount' => __( 'Discount', 'wpsc' ),
				'start' => __( 'Start', 'wpsc' ),
				'expiry' => __( 'Expiry', 'wpsc' ),
				'active' => __( 'Active', 'wpsc' ),
				'apply_on_prods' => __( 'Apply On All Products', 'wpsc' ),
				'edit' => __( 'Edit', 'wpsc' )
			);
			register_column_headers( 'display-coupon-details', $columns );
		?>

		<table class="coupon-list widefat" cellspacing="0">
			<thead>
				<tr>
					<?php print_column_headers( 'display-coupon-details' ); ?>
				</tr>
			</thead>

			<tfoot>
				<tr>
					<?php print_column_headers( 'display-coupon-details', false ); ?>
				</tr>
			</tfoot>

			<tbody>
				<?php
					$i = 0;
					$coupon_data = $wpdb->get_results( "SELECT * FROM `" . WPSC_TABLE_COUPON_CODES . "` ", ARRAY_A );

					foreach ( (array)$coupon_data as $coupon ) {
						$alternate = "";
						$i++;
						if ( ($i % 2) != 0 ) {
							$alternate = "class='alt'";
						}
						echo "<tr $alternate>\n\r";

						echo "    <td>\n\r";
						esc_attr_e( $coupon['coupon_code'] );
						echo "    </td>\n\r";

						echo "    <td>\n\r";
						if ( $coupon['is-percentage'] == 1 )
							echo esc_attr( $coupon['value'] ) . "%";

						else if ( $coupon['is-percentage'] == 2 ){
							if(!empty($coupon['free-shipping']))
								echo __("Free Shipping - With Conditions ", 'wpsc');
							else
								echo __("Free Shipping - Global", 'wpsc');
							
						}
						else
							echo wpsc_currency_display( esc_attr( $coupon['value'] ) );

						echo "    </td>\n\r";

						echo "    <td>\n\r";
						echo date( "d/m/Y", strtotime( esc_attr( $coupon['start'] ) ) );
						echo "    </td>\n\r";

						echo "    <td>\n\r";
						echo date( "d/m/Y", strtotime( esc_attr( $coupon['expiry'] ) ) );
						echo "    </td>\n\r";

						echo "    <td>\n\r";
						switch ( $coupon['active'] ) {
							case 1:
								echo "<img src='" . WPSC_CORE_IMAGES_URL . "/yes_stock.gif' alt='' title='' />";
								break;

							case 0: default:
								echo "<img src='" . WPSC_CORE_IMAGES_URL . "/no_stock.gif' alt='' title='' />";
								break;
						}
						echo "    </td>\n\r";

						echo "    <td>\n\r";
						switch ( $coupon['every_product'] ) {
							case 1:
								echo "<img src='" . WPSC_CORE_IMAGES_URL . "/yes_stock.gif' alt='' title='' />";
								break;

							case 0: default:
								echo "<img src='" . WPSC_CORE_IMAGES_URL . "/no_stock.gif' alt='' title='' />";
								break;
						}

						echo "    </td>\n\r";
						echo "    <td>\n\r";
						echo "<a title='" . esc_attr( $coupon['coupon_code'] ). "' href='#' rel='" . $coupon['id'] . "' class='wpsc_edit_coupon'  >" . __( 'Edit', 'wpsc' ) . "</a>";
						echo "    </td>\n\r";
						echo "  </tr>\n\r";
						echo "  <tr class='coupon_edit'>\n\r";
						echo "    <td colspan='7' style='padding-left:0px;'>\n\r";
						echo "      <div id='coupon_box_" . $coupon['id'] . "' class='displaynone modify_coupon' >\n\r";
						coupon_edit_form( $coupon );
						echo "      </div>\n\r";
						echo "    </td>\n\r";
						echo "  </tr>\n\r";
					}
				?>
			</tbody>
		</table>

		<p style='margin: 10px 0px 5px 0px;'>
			<?php _e( '<strong>Note:</strong> Due to a current PayPal limitation, when a purchase is made using a coupon we cannot send a detailed list of items through for processing. Instead we send the total amount of the purchase so the customer will see your shop name and the total within PayPal.', 'wpsc' ); ?>
		</p>

	</div>

<?php

}

?>