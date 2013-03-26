<script>
jQuery(document).ready(function() {
    var creditCard = jQuery('#gb_credit_cc_number');
    
    creditCard.cardcheck({
        acceptedCards: [
             <?php if ( !empty( $accepted_cards ) ) { // uses the payment processors accepted_cards() function
                echo "'" . implode( "', '", $accepted_cards ) . "'";
            } else {  // This is the default. Uncomment any CC below to manually override
                ?>
                    'visa',
                    'mastercard',
                    'amex',
                    // 'diners',
                    // 'discover',
                    // 'jcb',
                    // 'maestro'
                <?php
            } ?>
        ],
        iconLocation: '#accepted_credit_cards',
        iconDir: '<?php echo GB_URL ?>/resources/img/credit-cards/',
        onReset: function() {           
            creditCard.removeClass('success', 'error');
        },
        onError: function( type ) {
            creditCard.removeClass('success').addClass('error');
        },
        onValidation: function( type, niceName ) {
            creditCard.removeClass('error').addClass('success');
        }

    });
    
   // Hide the CC option when not selected
    jQuery('input:radio[name=gb_credit_payment_method]').live( 'click', function() {
        var $cc_option_value = jQuery(this).val();
        if ( $cc_option_value !== 'credit') {
            jQuery('.gb_credit_card_field_wrap').fadeOut();
        }
        else {
            jQuery('.gb_credit_card_field_wrap').fadeIn();
        };
    });
    
});
</script>
<span id="accepted_credit_cards" class="payment_method"><!-- Icons Automatically Inserted Here --></span><!-- #accepted_credit_cards.payment_method -->