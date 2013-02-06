<?php do_action( 'gb_meta_box_deal_attributes_pre' ) ?>
<script type="text/javascript">
jQuery(document).ready( function($) {
	$('a.gb-deal-attribute-remove').live( 'click', function() {
		$(this).parents('.gb-deal-attributes-row').remove();
		return false;
	});
	$('a#gb_add_attribute').click( function() {
		var $row = $('table#gb-deal-attributes-template .gb-deal-attributes-row').clone();
		$('div#gb-deal-attributes table tbody:first').append($row);
		return false;
	});
});
</script>
<?php 
	$categories = Group_Buying_Attribute::get_attribute_taxonomies(); ?>
<div id="gb-deal-attributes">
	<table class="widefat">
		<thead>
			<tr>
				<th id="attributes_sku"><?php gb_e( 'Sku' ); ?></th>
				<th id="attributes_label"><?php gb_e( 'Label' ); ?></th>
				<th id="attributes_price"><?php gb_e( 'Price' ); ?></th>
				<th id="attributes_max_purch"><?php gb_e( 'Max.&nbsp;Purchases' ); ?></th>
				<th id="attributes_desc"><?php gb_e( 'Description' ); ?></th>
				<?php if ( !empty($categories) ): ?><th><?php gb_e( 'Category' ); ?></th><?php endif; ?>
				<th id="attributes_remove"></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $attributes as $post_id => $data ): ?>
				<?php if ( !is_numeric( $post_id ) ) { $post_id = 0; } ?>
				<tr class="gb-deal-attributes-row">
					<td class="sku">
						<input class="gb-deal-attribute hidden" type="hidden" value="<?php echo $post_id; ?>" name="gb-attribute[attribute_id][]" />
						<input class="gb-deal-attribute" type="text" size="10" value="<?php esc_attr_e( $data['sku'] ); ?>" name="gb-attribute[sku][]" placeholder="<?php gb_e( 'Sku: ' ); echo $post_id; ?>" />
					</td>
					<td class="title"><input disabled="disabled" class="gb-deal-attribute" type="text" size="15" value="<?php esc_attr_e( $data['title'] ); ?>" name="gb-attribute[title][]" placeholder="<?php gb_e( 'Title' ); ?>" /></td>
					<td class="price"><input class="gb-deal-attribute" type="text" size="10" placeholder="<?php gb_e( 'Default' ); ?>" value="<?php if ( $data['price']!=Group_Buying_Attribute::DEFAULT_PRICE ) {esc_attr_e( $data['price'] );} ?>" name="gb-attribute[price][]" /></td>
					<td class="max_purchases"><input class="gb-deal-attribute" type="text" size="5" value="<?php esc_attr_e( $data['max_purchases'] ); ?>" name="gb-attribute[max_purchases][]" /></td>
					<td class="description"><textarea name="gb-attribute[description][]" cols="20" rows="3" placeholder="<?php gb_e( 'Description' ); ?>"><?php echo esc_textarea( $data['description'] ); ?></textarea></td>
					<td class="category">
						<table width="100%" class="widefat">
						<?php foreach ( Group_Buying_Attribute::get_attribute_taxonomies() as $taxonomy ): ?>
							<tr>
								<th scope="row"><label for="<?php echo 'gb-attribute[category]['.$taxonomy->name.'][]'; ?>"><?php echo $taxonomy->labels->name; ?>: </label></th>
								<td>
									<?php wp_dropdown_categories( array(
											'show_option_none' => '-- '.gb__( 'None' ).' --',
											'taxonomy' => $taxonomy->name,
											'selected' => isset( $data['categories'][$taxonomy->name] )?$data['categories'][$taxonomy->name]:0,
											'name' => 'gb-attribute[category]['.$taxonomy->name.'][]',
											'hide_empty' => FALSE,
										) ); ?>
								</td>
							</tr>
						<?php endforeach; ?>
						</table>
					</td>
					<td class="remove" valign="middle"><a type="button" class="button gb-deal-attribute-remove" href="#" title="<?php gb_e( 'Remove New Option' ); ?>"><?php gb_e( 'Remove' ); ?></a></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
<h4><a class="" href="#" id="gb_add_attribute"><?php gb_e( '+ Add Another Option' ); ?></a></h4>
<table id="gb-deal-attributes-template" style="display: none;"><tbody>
	<tr class="gb-deal-attributes-row">
		<td class="sku">
			<input class="gb-deal-attribute hidden" type="hidden" value="0" name="gb-attribute[attribute_id][]" />
			<input class="gb-deal-attribute" type="text" size="10" value="" name="gb-attribute[sku][]" placeholder="<?php gb_e( 'Sku' ); ?>" />
		</td>
		<td class="title"><input class="gb-deal-attribute" type="text" size="15" value="" name="gb-attribute[title][]" placeholder="<?php gb_e( 'Title' ); ?>" /><p class="description"><small><?php gb_e( '(Permanent & Required)' ) ?></small></p></td>
		<td class="price"><input class="gb-deal-attribute" type="text" size="10" value="" name="gb-attribute[price][]" placeholder="<?php gb_e( 'Default' ); ?>" /><p class="description"><small><?php gb_e( '(Leave blank to use Deal price)' ) ?></small></p></td>
		<td class="max_purchases"><input class="gb-deal-attribute" type="text" size="5" value="" name="gb-attribute[max_purchases][]" /></td>
		<td class="description"><textarea name="gb-attribute[description][]" cols="20" rows="3" placeholder="<?php gb_e( 'Description' ); ?>"></textarea></td>
		<?php if ( !empty($categories) ): ?>
			<td class="category">
				<table width="100%" class="widefat">
				<?php foreach ( Group_Buying_Attribute::get_attribute_taxonomies() as $taxonomy ): ?>
					<tr>
						<th scope="row"><label for="<?php echo 'gb-attribute[category]['.$taxonomy->name.'][]'; ?>"><?php echo $taxonomy->labels->name; ?>: </label></th>
						<td>
							<?php wp_dropdown_categories( array(
								'show_option_none' => '-- '.gb__( 'None' ).' --',
								'taxonomy' => $taxonomy->name,
								'selected' => 0,
								'name' => 'gb-attribute[category]['.$taxonomy->name.'][]',
								'hide_empty' => FALSE,
							) ); ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</table>
			</td>
		<?php endif ?>
		<td class="remove" valign="middle"><a type="button" class="button gb-deal-attribute-remove" href="#" title="<?php gb_e( 'Remove This Option' ); ?>"><?php gb_e( 'Remove' ); ?></a></td>
	</tr>
</tbody></table>
<?php do_action( 'gb_meta_box_deal_attributes' ) ?>
