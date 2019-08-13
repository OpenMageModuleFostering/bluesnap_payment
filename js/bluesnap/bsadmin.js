function bsencrypt(){
	var bskey =  $('bspubkey').value;
     var bsnp = new BlueSnap(bskey);
     bsnp.encrypt('payment_form_cse');  
}

function setevent(){
	var y = document.querySelector(".order-totals-bottom button");
	var t =document.getElementById("submit_order_top_button");
	y.setAttribute('onclick','bsencrypt(),order.submit()');
	bsencrypt();
}

function removeSpaceCc(el){
	var val = el.value.replace(/\s/g, "");
	document.getElementById("cse_cc_number").value=val;
	$('cse_cc_last').value = $('cse_cc_number').value.substr(val.length - 4);
	bsencrypt();
}

Event.observe(window, "load", onload, false);
function onload(){
	 var validator = new Validation(this.form);
            
            Validation.creditCartTypes.set('DC', [new RegExp('^3(?:0[0-5]|[68][0-9])[0-9]{11}$'), new RegExp('^[0-9]{3}$'), true]);
            Validation.creditCartTypes.set('CB', [new RegExp('^4[0-9]{12}([0-9]{3})?$'), new RegExp('^[0-9]{3}$'), true]);
}
			