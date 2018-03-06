(function($){
    function EnableApply() {

        var OriginalCaptcha = $('#careersForm').data('captchaText');
        var userCapcha = $('#captchaText').val();
        if (OriginalCaptcha == userCapcha) {
            $('#careerbtn').removeAttr('disabled');
        }
        else {
            $('#careerbtn').attr('disabled', 'disabled');
        }
    }

    function RegisterCapcha() {
        $("#captcha").html(''); //reset the generated captcha first
        $("#captchaText").val('');
        $("#careersForm").clientSideCaptcha({
            input: "#captchaText",
            display: "#captcha",
        });            
    }
}(jQuery));

var multiple = false;
var combi = false;
var waters = false;
var threed = false;
var confs = 0;
var freq = 0;
var step = 0.0;
var dstep = 0.0;
var email = "";
var tos = false;
var molList = "";
var modList = "";
var cutList = "";
var res = "";
var params;

function check() {
	combi = $("#combi").prop("checked");
	multiple = $("#multiple").prop("checked");
	waters = $("#waters").prop("checked");
	threed = $("#threed").prop("checked");
	res = "1920 1080";
	confs = parseInt($("#confs").val());
	freq = parseInt($("#freq").val());
	step = parseFloat($("#step").val());
	dstep = parseFloat($("#dstep").val());
	email = $("#email").val();
	tos = $("#tos").prop("checked");

	if ($("#res1080").is(":checked")) {
        res = "1920 1080";
	} else if ($("#res720").is(":checked")) {
        res = "1280 720";
	} else if ($("#res480").is(":checked")) {
        res = "640 480";
	}

	if (isNaN(confs) 
		|| isNaN(freq) 
		|| isNaN(step) 
		|| isNaN(dstep)) {

		return "One or more inputs are invalid." + freq + step + dstep;
	}
	if (regEx($("#mol-list").val(), "^([A-Z0-9][A-Z0-9]?[A-Z0-9]?( ?))*$")) {
		molList = $("#mol-list").val();
	} else if (!$("#mol-list").val() == "") {
		return "Format of molecule list is incorrect.";
	}

	if (regEx($("#modes-list").val(), "^(([0-9])([0-9]?)( ?))*$")) {

		modList = $("#modes-list").val();
	} else if (!$("#modes-list").val() == "") {
		return "Format of modes to calculate is incorrect.";
	}

	if (regEx($("#cutoff-list").val(), "^(-[0-9].[0-9]+( ?))*$")) {
		cutList = $("#cutoff-list").val();
	} else if (!$("#cutoff-list").val() == "") {
		return "Format of cutoff value list is incorrect.";
	}

	if (!validateEmail(email)) {
		return "Invalid email.";
	}

	if (tos == false) {
		return "Stop messing with my JavaScript.";
	}

	params = {
		"res"		: res,
		"combi"		: combi, 
		"multiple"	: multiple, 
		"waters"	: waters,
		"threed"	: threed,
		"confs"		: confs,
		"freq"		: freq,
		"step"		: step,
		"dstep"		: dstep,
		"email"		: email,
		"tos"		: tos,
		"molList"	: molList,
		"modList" 	: modList,
		"cutList" 	: cutList 
		};
	return "Success";
}



var form = document.querySelector("form");

form.addEventListener("submit", function (e) {
  
  // Prevents the standard submit event
  e.preventDefault();

	var result = check();
	if (result !== "Success") {
		alert(result); 
		throw(result);
	}

  var fData = new FormData(this);
  // Optional. Append custom data.
	$.each(params, function(key, value){
	  fData.append(key, value);
	})
	
  $.ajax({
    url: window.location.pathname + "php/index.php",
	type: 'POST',
    data: fData,
    async: false,
    success: function (data) {
   	alert(data)
    },
    cache: false,
    contentType: false,
    processData: false
	});

  return false;

}, false);
	
function regEx(subj, exp) {
	var re = new RegExp(exp);
	return re.test(subj);
}

function validateEmail(email) {
  var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
  return re.test(email);
}

function tosClick() {
	tos = $("#tos").prop("checked");
	$("#process").prop("disabled", !tos);
}

function fadeOut() {
	$('#foo').fadeOut();
}
function fadeIn() { 
	$('#foo').fadeIn();
}

if ($("#tos").prop("checked") == false) {
	$("#process").prop("disabled", true);
}  else {
	$("#process").prop("disabled", false);
}
