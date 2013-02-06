<br/>
<form id="claim_voucher" action="" method="post">

	<table class="form-table">
		<tbody>
				<tr>
					<td colspan="2" class="heading">
						<?php gb_e( 'Mark Voucher Redeemed' ) ?>
					</td>
				</tr>
			<tr>
				<td>
					<label for="gb_voucher_claim">
						<?php gb_e( 'Security Code' ) ?>
					</label>
				</td>
				<td class="gb-form-field gb-form-field-text">
					<input type="text" name="<?php echo $claim_arg ?>" id="<?php echo $claim_arg ?>" value="<?php if ( isset( $_GET['gb_voucher_claim'] )&&$_GET['gb_voucher_claim']!='' ) echo $_GET['gb_voucher_claim'] ?>" />
				</td>
			</tr>
			<tr>
				<td>
					<label for="gb_voucher_claim">
						<?php gb_e( 'Redeemers Name' ) ?>
					</label>
				</td>
				<td class="gb-form-field gb-form-field-text">
					<input type="text" name="<?php echo $data.'[name]' ?>" id="<?php echo $data.'[name]' ?>" />
				</td>
			</tr>
			<tr>
				<td>
					<label for="gb_voucher_claim">
						<?php gb_e( 'Redemption Date' ) ?>
					</label>
				</td>
				<td class="gb-form-field gb-form-field-text">
					<input type="text" name="<?php echo $data.'[date]' ?>" id="<?php echo $data.'[date]' ?>" />
				</td>
			</tr>
			<tr>
				<td>
					<label for="gb_voucher_claim">
						<?php gb_e( 'Total Paid' ) ?>
					</label>
				</td>
				<td class="gb-form-field gb-form-field-text">
					<input type="text" name="<?php echo $data.'[total]' ?>" id="<?php echo $data.'[total]' ?>" />
				</td>
			</tr>
			<tr>
				<td>
					<label for="gb_voucher_claim">
						<?php gb_e( 'Notes' ) ?>
					</label>
				</td>
				<td class="gb-form-field gb-form-field-text">
					<textarea type="textarea" name="<?php echo $data.'[notes]' ?>" id="<?php echo $data.'[notes]' ?>"></textarea>
				</td>
			</tr>
		</tbody>
	</table>
	<?php
	if ( isset( $_GET['redirect_to'] ) && $_GET['redirect_to'] != '' ) {
		echo '<input type="hidden" name="redirect_to" value="'.$_GET['redirect_to'].'">';
	}
?>
	<input type="submit" class="form-submit" value="<?php gb_e( 'Submit' ); ?>" />
</form>
