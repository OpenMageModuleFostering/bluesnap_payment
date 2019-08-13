(function($) {
    //https://github.com/jessepollak/card/blob/master/lib/js/jquery.card.js
    //https://github.com/jondavidjohn/payform#custom-cards
    $.validateCreditCard = function(number,month,year,type) {
        return payform.validateCardNumber(number) && payform.validateCardExpiry(month, year) && payform.validateCardCVC(cvc, type);
    }

    $.getCreditCardType = function(val) {
        return payform.parseCardType(val); 
    };
	
    $.creditCardType = function(options) {
        //formatter
        //https://learn.jquery.com/using-jquery-core/faq/how-do-i-pull-a-native-dom-element-from-a-jquery-object/

        var settings = {
            target: '#credit-card-type'
        };

        if(options) {
            $.extend(settings, options);
        }

        var ccNumberKeyupHandler = function() {
            $(".pli-credit-cards span").removeClass("pli-active");
            $("select[id=cse_cc_type]").val('');
            creditCardType=$.getCreditCardType($(this).val());

            switch(creditCardType) {
                case 'visa':
                    $('.pli-cc-VISA').addClass('pli-active');
                    $("select[id=cse_cc_type]").val('VI');
                    break;
                case 'mastercard':
                    $('.pli-cc-MASTERCARD').addClass('pli-active');
                    $("select[id=cse_cc_type]").val('MC');
                    break;
                case 'amex':
                    $('.pli-cc-AMEX').addClass('pli-active');
                    $("select[id=cse_cc_type]").val('AE');
                    break;
                case 'discover':
                    $('.pli-cc-DISCOVER').addClass('pli-active');
                    $("select[id=cse_cc_type]").val('DI');
                    break;
                case 'jcb':
                    $('.pli-cc-JCB').addClass('pli-active');
                    $("select[id=cse_cc_type]").val('JCB');
                    break;
                case 'dinersclub':
                    $('.pli-cc-DINERS').addClass('pli-active');
                    $("select[id=cse_cc_type]").val('DC');
                    break;    
                case 'carteblue':
                    $('.pli-cc-CARTE_BLEUE').addClass('pli-active');
                    $("select[id=cse_cc_type]").val('CB');
                    break;
            };

            if($(".validation-advice")) {
                $(".validation-advice").hide();
            }
            if(payform.validateCardNumber($("input[id=cse_cc_number]").val())) {
                $("input[id=cse_cc_number]")[0].setStyle({'borderColor':'green'});
            } else {
                $("input[id=cse_cc_number]")[0].setStyle({'borderColor':'red'});
            }

        };

        var ccCvvKeyupHandler = function() {
            if($('#advice-required-entry-cse_cc_cid')) {
                $('#advice-required-entry-cse_cc_cid').hide();
            }
			var card = payform.cardFromNumber($("input[id=cse_cc_number]").val());

            if(payform.validateCardCVC($("input[id=cse_cc_cid]").val(), card.type)) {
                $("input[id=cse_cc_cid]")[0].setStyle({'borderColor':'green'});

            } else {
                $("input[id=cse_cc_cid]")[0].setStyle({'borderColor':'red'});
            }

        };

        var ccExpChangeHandler = function() {
            if(payform.validateCardExpiry($("select[id=cse_expiration]").val(),$("select[id=cse_expiration_yr]").val())) {
                $("select[id=cse_expiration]")[0].setStyle({'borderColor':'green'});
                $("select[id=cse_expiration_yr]")[0].setStyle({'borderColor':'green'});
            } else {
                $("select[id=cse_expiration]")[0].setStyle({'borderColor':'red'});
                $("select[id=cse_expiration_yr]")[0].setStyle({'borderColor':'red'});
            }


        };

        var ccNumberBlurHandler = function() {
            if(payform.validateCardNumber($("input[id=cse_cc_number]").val())) {
                $("#cse_cc_number_valid").show();
                $("#cse_cc_number_not_valid").hide();

                $("#cse_cc_type_valid").show();
                $("#cse_cc_type_not_valid").hide();

            } else {
                $("#cse_cc_number_valid").hide();
                $("#cse_cc_number_not_valid").show();

                $("#cse_cc_type_valid").hide();
                $("#cse_cc_type_not_valid").show();
            }
        }

        var ccCidBlurHandler = function() {		
			var card = payform.cardFromNumber($("input[id=cse_cc_number]").val());

            if(payform.validateCardCVC($("input[id=cse_cc_cid]").val(), card.type)) {
                $("#cse_cc_cid_valid").show();
                $("#cse_cc_cid_not_valid").hide();

            } else {
                $("#cse_cc_cid_valid").hide();
                $("#cse_cc_cid_not_valid").show();

            }
        }

        var ccExpBlurHandler = function() {
            if(payform.validateCardExpiry($("#cse_expiration").val(),$("#cse_expiration_yr").val() )  ) {
                $("#cse_cc_expiration_valid").show();
                $("#cse_cc_expiration_not_valid").hide();

            } else {
                $("#cse_cc_expiration_valid").hide();
                $("#cse_cc_expiration_not_valid").show();
            }
        }

        return $("#cse_cc_number").each(function() {
            $(this).on('keyup', ccNumberKeyupHandler);
            $("input[id=cse_cc_cid]").bind('keyup',ccCvvKeyupHandler);
            $("select[id=cse_expiration]").bind('change',ccExpChangeHandler);
            $("select[id=cse_expiration_yr]").bind('change',ccExpChangeHandler);

            $("input[id=cse_cc_number]").bind('blur',ccNumberBlurHandler);
            $("input[id=cse_cc_cid]").bind('blur',ccCidBlurHandler);

            $("select[id=cse_expiration]").bind('blur',ccExpBlurHandler);
            $("select[id=cse_expiration_yr]").bind('blur',ccExpBlurHandler);


        });


    };
})(jQuery);


function removeSpaceCc(el){
		var val = el.value.replace(/\s/g, "");
		document.getElementById("cse_cc_number").value=val;
		
		if(document.getElementById("cse_cc_type").value=='' || payform.parseCardType(val) != getType(document.getElementById("cse_cc_type").value)){
			if(document.getElementById("cse_cc_type").value!='') jQuery(".pli-credit-cards span").removeClass("pli-active");
			var creditCardType = payform.parseCardType(val);
			 switch(creditCardType) {
                case 'visa':
                    jQuery('.pli-cc-VISA').addClass('pli-active');
                    jQuery("select[id=cse_cc_type]").val('VI');
                    break;
                case 'mastercard':
                    jQuery('.pli-cc-MASTERCARD').addClass('pli-active');
                    jQuery("select[id=cse_cc_type]").val('MC');
                    break;
                case 'amex':
                    jQuery('.pli-cc-AMEX').addClass('pli-active');
                    jQuery("select[id=cse_cc_type]").val('AE');
                    break;
                case 'discover':
                    jQuery('.pli-cc-DISCOVER').addClass('pli-active');
                    jQuery("select[id=cse_cc_type]").val('DI');
                    break;
                case 'jcb':
                    jQuery('.pli-cc-JCB').addClass('pli-active');
                    jQuery("select[id=cse_cc_type]").val('JCB');
                    break;
                case 'dinersclub':
                    jQuery('.pli-cc-DINERS').addClass('pli-active');
                    jQuery("select[id=cse_cc_type]").val('DC');
                    break;    
                case 'carteblue':
                    jQuery('.pli-cc-CARTE_BLEUE').addClass('pli-active');
                    jQuery("select[id=cse_cc_type]").val('CB');
                    break;
            };

            if(jQuery(".validation-advice")) {
                jQuery(".validation-advice").hide();
            }
                    
		}
	}
	
	function getType(code){
		 switch(code) {
                case 'VI':
                    return "visa";
                    break;
                case 'MC':
                    return 'mastercard';
                    break;
                case 'AE':
                    return "amex";
                    break;
                case 'DI':
                    return 'discover';
                    break;
                case 'JCB':
                    return 'jcb';
                    break;
                case 'DC':
                    return "dinersclub";
                    break;    
                case 'CB':
                    return 'carteblue';
                    break;
            };	
	}
