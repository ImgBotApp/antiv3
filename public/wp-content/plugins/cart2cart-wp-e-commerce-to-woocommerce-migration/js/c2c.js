jQuery(document).ready(function($) {

	var HTTP_API_PATH = 'https://app.shopping-cart-migration.com/capi/';
	//var SELF_PATH = '?route=module/c2c/';
	var SELF_PATH = 'plugins.php?page=cart2cart-config&c2caction=';
	//var opencartToken = getParameter('token');

	var messages		= $('#messages');
	var loggedEmail	= $('#loggedEmail');

	var loginCart2cartAccount 	=$("#loginCart2cartAccount");
	var loginCart2cartPass 	=$("#loginCart2cartPass");
	var cart2CartLoginKey 	=$("#cart2CartLoginKey");
	var cart2CartLoginEmail =$("#cart2CartLoginEmail");
	var submitLoginForm 	=$("#submitLoginForm");

	var registerCart2cartName 	=$("#registerCart2cartName");
	var registerCart2cartAccount=$("#registerCart2cartAccount");
	var registerCart2cartPass 	=$("#registerCart2cartPass");
    var registerRefererText 	=$("#registerRefererText");
	var submitRegisterForm 	=$("#submitRegisterForm");


	var Cart2cartRemoteHost		=$("#Cart2cartRemoteHost");
	var Cart2cartRemoteUsername	=$("#Cart2cartRemoteUsername");
	var Cart2cartRemotePassword	=$("#Cart2cartRemotePassword");
	var Cart2cartRemoteDirectory	=$("#Cart2cartRemoteDirectory");
	var submitCart2cartRemoteForm	=$("#submitCart2cartRemoteForm");

	var showButton			=$("#showButton");
	var Cart2cartConnectionInstall	=$("#Cart2cartConnectionInstall");
	var Cart2cartConnectionUninstall	=$("#Cart2cartConnectionUninstall");

	var storeToken			=$("#storeToken");
	var isLogged			=$("#isLogged");

	var action = 'void';

	var logout =$(".logout");

	logout.on("click",function(){
		logoff();
	});

	if (showButton.val() == 'install'){
		Cart2cartConnectionUninstall.hide();
		Cart2cartConnectionInstall.show();
	}else{
		Cart2cartConnectionInstall.hide();
		Cart2cartConnectionUninstall.show();
	}

	function getParameter(paramName) {
		var searchString = window.location.search.substring(1),	i, val, params = searchString.split("&");
		for (i=0;i<params.length;i++) {
			val = params[i].split("=");
			if (val[0] == paramName) {
				return unescape(val[1]);
			}
		}
		return null;
	}

	function errorMessage(message,type){
		var messageText = new Array();
		console.log(type);
		if (type == 'error'){
			messages.removeClass("successMessage");
			messages.addClass("errorMessage");
		}
		if (type == 'success'){
			messages.removeClass("errorMessage");
			messages.addClass("successMessage");
		}
		messages.text(message);
		messages.show();
		setTimeout(function () {
    			messages.hide();
			}, 5000);
	}

	function login(email,pass){
		var encPass = hex_md5(email.toLowerCase()+pass);
		$.ajax({
			cache: false,
			url: HTTP_API_PATH+"login",
			dataType: "jsonp",
			jsonpCallback: "callback",
			data: {"email" : email, "pass" : encPass},
			success: function(data){
				if (data.error == false){
					errorMessage(data.data,'success');
					saveLoginStatus('Yes',email,encPass)

				} else {
					errorMessage(data.data,'error');
				}
			}
		});
	}

	function logoff(){
		$.ajax({
			cache: false,
			url: HTTP_API_PATH+"logout",
			dataType: "jsonp",
			jsonpCallback: "callback",
			success: function(){
				saveLoginStatus('No','','');

			}
		});
	}

    function register(name,email,pass, referer){
		$.ajax({
			cache: false,
			url: HTTP_API_PATH+"register",
			dataType: "jsonp",
			jsonpCallback: "callback",
            data: {"email" : email, "pass" : pass, "fullname" : name, "referer" : referer},
			success: function(data){
				if (data.error == false){
					clearFtpInfo();
					errorMessage('Register success','success');
					console.log("register success, try to login");
					login(email,pass);
				} else {
					errorMessage(data.data,'error');
				}
			}
		});
	}

	function clearFtpInfo(install){
		console.log("try to clear settings");
		$.ajax({
			cache: false,
			url: SELF_PATH +'clearFtpInfo'
		});
	}


	function saveLoginStatus(status,email,encPass){
		$.ajax({
			cache: false,
			url: SELF_PATH+"saveLoginStatus&status="+status+"&email="+encodeURIComponent(email)+'&encPass='+encPass,
			success:function(){
				window.location.reload();
			}
		});
	}

	function getToken(){
		var email =cart2CartLoginEmail.val();
		var key = window.btoa(cart2CartLoginKey.val());

		$.ajax({
			cache: false,
			url: HTTP_API_PATH+"get-token",
			dataType: "jsonp",
			jsonpCallback: "callback",
			data: {"email" : email, "key" : key},
			success: function(data){
				if (data.error == false){
					saveToken(data.data.token);
				} else {
					errorMessage(data.data,'error');
				}
			}
		});
	}

	function saveToken(token){
		console.log('try to save token '+ token);
		$.ajax({
			url: SELF_PATH + "saveToken&c2c_token="+token,
			success: function(){
				console.log('token '+ token + ' saved');
				storeToken.val(token);
				doAction(action);
				action = 'void';
			}
		});
	}

	function sendBridgeRequest(install){
		console.log("try to "+install+"-bridge");
		$.ajax({
			cache: false,
			url: SELF_PATH + install+'Bridge',
			success: function(){
				if (install == 'install'){
					showButton.val('uninstall');
					Cart2cartConnectionInstall.hide();
					Cart2cartConnectionUninstall.show();
					errorMessage('Connection Bridge installed','success');
				}else{
					showButton.val('install');
					Cart2cartConnectionUninstall.hide();
					Cart2cartConnectionInstall.show();
					errorMessage('Connection Bridge uninstalled','success');
				}
				$("#bridgeajaxloader").hide();
				console.log(install+"-bridge");
			}
		});
	}

	function saveFtpInfo(host,user,pass,dir){
		console.log('try to save FtpInfo ');
		$.ajax({
			cache: false,
			url: SELF_PATH + "saveFtp&host="+host+"&user="+user+"&pass="+encodeURIComponent(pass)+"&dir="+dir,
			success: function(response){
				var data = eval('(' + response + ')');
				errorMessage(data.messages,data.messageType);
				$("#ajaxloader").hide();
				console.log('FtpInfo saved');
			}
		});
	}

	submitCart2cartRemoteForm.on("click",function(){
		action = 'installRemote';
		var hasError = false;
		$(".c2cReqired").hide();
		Cart2cartRemoteHost.removeClass('c2cReqiredField');
		Cart2cartRemoteUsername.removeClass('c2cReqiredField');
		if (Cart2cartRemoteHost.val() == ''){
			Cart2cartRemoteHost.addClass('c2cReqiredField');
			$("#hostError").html('FTP host is required');
			$("#hostError").animate({
				'opacity': 'show'
			});
			hasError = true;
		}
		if (Cart2cartRemoteUsername.val() == ''){
			Cart2cartRemoteUsername.addClass('c2cReqiredField');
			$("#hostUser").html('FTP username is required');
			$("#hostUser").animate({
				'opacity': 'show'
			});
			hasError = true;
		}
		if (!hasError){
			$(".c2cReqired").hide();
			$("#ajaxloader").show();
			getToken();
		}
	});

	submitLoginForm.on("click",function(){
		var hasError = false;
		$(".c2cReqired").hide();

		loginCart2cartAccount.removeClass('c2cReqiredField');
		loginCart2cartPass.removeClass('c2cReqiredField');
		if (loginCart2cartAccount.val() == ''){
			loginCart2cartAccount.addClass('c2cReqiredField');
			$("#cart2cartAccount").html('Email is required');
			$("#cart2cartAccount").animate({
				'opacity': 'show'
			});
			hasError = true;
		}
		if (loginCart2cartPass.val() == ''){
			loginCart2cartPass.addClass('c2cReqiredField');
			$("#cart2cartPass").html('Password is required');
			$("#cart2cartPass").animate({
				'opacity': 'show'
			});
			hasError = true;
		}
		if (!hasError){
			$(".c2cReqired").hide();
			login(
				loginCart2cartAccount.val(),
				loginCart2cartPass.val()
			);
		}
	});

	submitRegisterForm.on("click",function(){
		var hasError = false;
		$(".c2cReqired").hide();

		registerCart2cartName.removeClass('c2cReqiredField');
		registerCart2cartAccount.removeClass('c2cReqiredField');
		registerCart2cartPass.removeClass('c2cReqiredField');
		if (registerCart2cartName.val()== ''){
			registerCart2cartName.addClass('c2cReqiredField');
			$("#registerAccountError").html('Name is required');
			$("#registerAccountError").animate({
				'opacity': 'show'
			});
			hasError = true;
		}

		if (registerCart2cartAccount.val()== ''){
			registerCart2cartAccount.addClass('c2cReqiredField');
			$("#registerEmailError").html('Email is required');
			$("#registerEmailError").animate({
				'opacity': 'show'
			});
			hasError = true;
		}
		if (registerCart2cartAccount.val().match(/[\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+\.[A-z]{2,3}/igm) == null){
			registerCart2cartAccount.addClass('c2cReqiredField');
			$("#registerEmailError").html('Please use valid email address');
			$("#registerEmailError").animate({
				'opacity': 'show'
			});
			hasError = true;
		}

		if (registerCart2cartPass.val().length < 6){
			registerCart2cartPass.addClass('c2cReqiredField');
			$("#registerPassError").html('Password must be greater than 5 symbols');
			$("#registerPassError").animate({
				'opacity': 'show'
			});
			hasError = true;
		}
		if (!hasError){
			$(".c2cReqired").hide();
			register(
				registerCart2cartName.val(),
				registerCart2cartAccount.val(),
				registerCart2cartPass.val(),
                registerRefererText.val()
			);
		}

	});

	Cart2cartConnectionInstall.on("click",function(){
		action = 'installLocal';
		$("#bridgeajaxloader").show();
		getToken();
	})
	Cart2cartConnectionUninstall.on("click",function(){
		if (confirm("Are you sure? You won't be able to migrate your data!")) {
			$("#bridgeajaxloader").show();
			sendBridgeRequest("remove");
		}
	})

	function doAction(action){
		switch (action){
			case 'installLocal':
				sendBridgeRequest("install");
			break
			case 'installRemote':
				saveFtpInfo(Cart2cartRemoteHost.val(),Cart2cartRemoteUsername.val(),Cart2cartRemotePassword.val(),Cart2cartRemoteDirectory.val());
			break
		}
	}


    $(function (){
        var tabContainers = $('div.tabs_content > div');
        var tabNavigation = $('div.tabs_content ul.nav_tabs a, div.tabs_content div a');
        tabContainers.hide().filter(':first').show();
        tabNavigation.click(function () {
            tabContainers.hide();
            tabContainers.filter(this.hash).show();
            tabNavigation.removeClass('selected');
            $('a[href='+this.hash+']').each(function(){
                $(this).addClass('selected');
            });
            location.hash = this.hash;
            return false;
        });

        var hash = window.location.hash;
        var elements = $('a[href="' + hash + '"]');
        if (elements.length !== 0) {
            elements.click();
        }
    });

})