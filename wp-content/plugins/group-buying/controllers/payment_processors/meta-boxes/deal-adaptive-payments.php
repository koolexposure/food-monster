<p>
	<label for="adaptive_primary"><strong><?php self::_e( 'Primary Receiver Email' ) ?>:</strong></label>
	<input type="text" id="adaptive_primary" name="adaptive_primary" value="<?php echo $primary; ?>" size="15" />
</p>
<p>
	<label for="adaptive_secondary"><strong><?php self::_e( 'Secondary Receiver Email' ) ?>:</strong></label>
	<input type="text" id="adaptive_secondary" name="adaptive_secondary" value="<?php echo $secondary; ?>" size="15" />
</p>
<p>
	<label for="adaptive_secondary_share"><strong><?php self::_e( 'Secondary Receiver Payment' ) ?>:</strong></label>
	<input type="text" id="adaptive_secondary_share" name="adaptive_secondary_share" value="<?php echo $secondary_share; ?>" size="5" />
</p>
<p><?php self::_e( '<strong>Notes:</strong> In a chained payment, the customer pays the primary receiver (you) an amount, from which the primary receiver (you) pays secondary receiver(s) (merchant). The customer only knows about the primary receiver (you), not the secondary receiver(s) (merchant). The secondary receiver(s) (merchant) only know about the primary receiver (you), not the customer.' ) ?></p>
