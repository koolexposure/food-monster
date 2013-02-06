<form id="gb_deal_submission" class="main_block" method="post" action="" enctype="multipart/form-data">
	<input type="hidden" name="gb_deal_submission" value="<?php esc_attr_e($form_action); ?>" />
	<table class="collapsable form-table">
		<tbody>
			<?php foreach ( $fields as $key => $data ): ?>
				<tr>
					<?php if ( $data['type'] == 'heading' ): ?>
						<td colspan="2" class="heading font_large bold" ><?php echo $data['label']; ?></td>
					<?php elseif( $data['type'] != 'checkbox' ): ?>
						<?php if ( isset( $data['label'] ) && $data['label'] ): ?>
							<td><?php gb_form_label( $key, $data, 'deal' ); ?></td>
							<td><?php gb_form_field($key, $data, 'deal'); ?></td>
						<?php else: ?>
							<td colspan='2'><?php gb_form_field($key, $data, 'deal'); ?></td>
						<?php endif ?>
					<?php else: ?>
						<td colspan="2">
							<label for="gb_contact_<?php echo $key; ?>"><?php gb_form_field($key, $data, 'deal'); ?> <?php echo $data['label']; ?></label>
						</td>
					<?php endif; ?>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php self::load_view('merchant/submit-controls', array()); ?>
</form>