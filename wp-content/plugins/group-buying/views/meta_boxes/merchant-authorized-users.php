<p>
	<strong><?php gb_e( 'Authorized Users' ); ?></strong><br />
	<?php
		foreach ( $authorized_users as $user_id ) {
			$user = get_userdata( $user_id );
			$display = "$user->user_firstname $user->user_lastname";
			if ( ' ' == $display ) {
				$display = $user->user_login;
			}
			if ( !empty( $user->user_email ) ) {
				$display .= " ($user->user_email)";
			}
			echo "$display<br />";
		} ?>
</p>
<p>
	<strong><?php gb_e( 'Authorize a User' ); ?></strong><br />

	<?php 
	// If over 100 users use an input field
	if ( count( $users ) > 100 ): ?>
		<script type="text/javascript">
			jQuery(document).ready( function($) {
				var $authorized_userid = $('#authorized_user');
				var $span = $('#authorized_user_ajax');
				
				var show_account = function() {
					$span.addClass('loading_gif').empty();
					var user_id = $authorized_userid.val();
					if ( !user_id ) {
						$span.removeClass('loading_gif');
						return;
					}
					$.ajax({
						type: 'POST',
						dataType: 'json',
						url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
						data: {
							action: 'gbs_ajax_get_account',
							id: user_id
						},
						success: function(data) {
							$span.removeClass('loading_gif');
							$span.empty().append(data.name + ' <span style="color:silver">(user id:' + data.user_id + ') (account id:' + data.account_id + ')</span>');
						}
					});
				};
				$authorized_userid.live('keyup',show_account);
			});
		</script>
		<style type="text/css">
			.loading_gif {
				background: url( '<?php echo GB_URL; ?>/resources/img/loader.gif') no-repeat 0 center;
				width: auto;
				height: 16px;
				padding-right: 16px;
				padding-bottom: 2px;
			}
		</style>
		<input name="authorized_user" id="authorized_user" type="text" size="8" placeholder="<?php gb_e('User ID')?>"/>
		<span id="authorized_user_ajax">&nbsp;</span>
	<?php else: ?>
		<select name="authorized_user" id="authorized_user" class="select2" style="width:300px;">
			<option value=""><?php gb_e( 'Select a User To Authorize' ); ?></option>
			<?php
				$authorized_user = $authorized_users[0];
				foreach ( $users as $user ) {
					if ( !in_array( $user->ID, $authorized_users) ) {
						
						$display = get_user_meta( $user->ID, 'first_name', TRUE ) . ' ' . get_user_meta( $user->ID, 'last_name', TRUE );
						if ( ' ' == $display ) {
							$display = $user->user_login;
						}
						if ( !empty( $user->user_email ) ) {
							$display .= " ($user->user_email)";
						}
						echo "<option value=\"$user->ID\">$display</option>";
					}
				} ?>
		</select>
	<?php endif ?>
</p>

<p>
	<strong><?php gb_e( 'Unauthorize a User' ); ?></strong><br />
	<select name="unauthorized_user" id="unauthorized_user" class="select2" style="width:300px;">
		<option value=""><?php gb_e( 'Select a User To Unuthorize' ); ?></option>
		<?php
			foreach ( $authorized_users as $user_id ) {
				$user = get_userdata( $user_id );
				$display = get_user_meta( $user_id, 'first_name', TRUE ) . ' ' . get_user_meta( $user_id, 'last_name', TRUE );
				if ( ' ' == $display ) {
					$display = $user->user_login;
				}
				if ( !empty( $user->user_email ) ) {
					$display .= " ($user->user_email)";
				}

				echo "<option value=\"$user->ID\">$display</option>";
			} ?>
	</select>
</p>
