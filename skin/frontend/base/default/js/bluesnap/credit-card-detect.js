(function($) {

    //https://github.com/jessepollak/card/blob/master/lib/js/jquery.card.js  
    //https://github.com/jondavidjohn/payform#custom-cards
    $.validateCreditCard = function(number,month,year,type) {


        return payform.validateCardNumber(number) && payform.validateCardExpiry(month, year) && payform.validateCardCVC(cvc, type);

    }


    $.getCreditCardType = function(val) {
        return payform.parseCardType(val); 
    };
    $.fn.creditCardType = function(options) {

        //formatter
        //https://learn.jquery.com/using-jquery-core/faq/how-do-i-pull-a-native-dom-element-from-a-jquery-object/
        //   payform.cardNumberInput(jQuery('#cse_cc_number')[0]);
        //  payform.cvcInput(jQuery('#cse_cc_cid')[0]);

        var settings = {
            target: '#credit-card-type',
        };
        if(options) {
            $.extend(settings, options);
        };
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
             //   $("#cse_cc_number_not_valid").tooltip("close");

            } else {
                $("input[id=cse_cc_number]")[0].setStyle({'borderColor':'red'});
            }

            //       if(payform.validateCardExpiry($("select[id=cse_expiration]").val(),$("select[id=cse_expiration_yr]").val())) {
            //          $("select[id=cse_expiration]")[0].setStyle({'borderColor':'green'});
            //          $("select[id=cse_expiration_yr]")[0].setStyle({'borderColor':'green'});
            //      } else {
            //          $("select[id=cse_expiration]")[0].setStyle({'borderColor':'red'});
            //          $("select[id=cse_expiration_yr]")[0].setStyle({'borderColor':'red'});
            //      }

            //       if(payform.validateCardCVC($("input[id=cse_cc_cid]").val())) {
            //          $("input[id=cse_cc_cid]")[0].setStyle({'borderColor':'green'});
            //     } else {
            //          $("input[id=cse_cc_cid]")[0].setStyle({'borderColor':'red'});
            //      }

        };

        var ccCvvKeyupHandler = function() {


            //       if(payform.validateCardExpiry($("select[id=cse_expiration]").val(),$("select[id=cse_expiration_yr]").val())) {
            //          $("select[id=cse_expiration]")[0].setStyle({'borderColor':'green'});
            //          $("select[id=cse_expiration_yr]")[0].setStyle({'borderColor':'green'});
            //      } else {
            //          $("select[id=cse_expiration]")[0].setStyle({'borderColor':'red'});
            //          $("select[id=cse_expiration_yr]")[0].setStyle({'borderColor':'red'});
            //      }

            if($('#advice-required-entry-cse_cc_cid')) {
                $('#advice-required-entry-cse_cc_cid').hide();
            }

            if(payform.validateCardCVC($("input[id=cse_cc_cid]").val())) {
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
                //  var myOpentip = new Opentip($("#cse_cc_number_not_valid"),{ showOn: null, style: 'alert' });
                //  myOpentip.setContent($("#cse_cc_number_not_valid").attr('title'));
                //  myOpentip.show();
                //  var tooltip = new Tooltip('cse_cc_number', $("#cse_cc_number_not_valid").attr('title'))
                //tooltip.show();  

                // Validation.validate($('co-payment-form'));

                // $("#cse_cc_number_not_valid").trigger('mouseover');
            }
        }


        var ccCidBlurHandler = function() {
            if(payform.validateCardCVC($("input[id=cse_cc_cid]").val())) {
                $("#cse_cc_cid_valid").show();
                $("#cse_cc_cid_not_valid").hide();

            } else {
                $("#cse_cc_cid_valid").hide();
                $("#cse_cc_cid_not_valid").show();
                // var myOpentip = new Opentip($("#cse_cc_cid_not_valid")[0]);
                // myOpentip.show();

                // var tooltip = new Tooltip('cse_cc_cid', $("#cse_cc_cid_not_valid").attr('title'))
                //tooltip.show();  

                //  $("#cse_cc_cid_not_valid").trigger("mouseover");

            }
        }

        var ccExpBlurHandler = function() {
            if(payform.validateCardExpiry($("#cse_expiration").val(),$("#cse_expiration_yr").val() )  ) {
                $("#cse_cc_expiration_valid").show();
                $("#cse_cc_expiration_not_valid").hide();

            } else {
                $("#cse_cc_expiration_valid").hide();
                $("#cse_cc_expiration_not_valid").show();
                // var myOpentip = new Opentip($("#cse_cc_cid_not_valid")[0]);
                // myOpentip.show();

                // var tooltip = new Tooltip('cse_cc_cid', $("#cse_cc_cid_not_valid").attr('title'))
                //tooltip.show();  

                //  $("#cse_cc_cid_not_valid").trigger("mouseover");

            }
        }


        return this.each(function() {
            $(this).bind('keyup',ccNumberKeyupHandler);
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
