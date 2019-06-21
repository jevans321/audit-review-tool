/**
 * Created by Matt Torres on 3/3/16.
 */
function checkAccess(){
	var data = {
		 "action": "checkAccess",
		"state": 'recheck'
	};
				
	data = $(this).serialize() + "&" + $.param(data);
	$.ajax({
	type: "POST",
	url: "/routes/index_class.php", //Relative or absolute path to response.php file
	data: data,
	success: function(data) {
		console.log(data);
			
		if(data["access"] === 'N'){
			//logOut('logoutframe','https://w3id.alpha.sso.ibm.com/pkmslogout');
			//$('#logoutframe').attr('src','https://w3id.alpha.sso.ibm.com/pkmslogout');
			document.cookie = 'PHPSESSID=; expires=Thu, 01 Jan 1970 00:00:00 UTC;';
			window.location.replace("https://"+window.location.hostname+"/index");
		}
	} //success
	}); //end ajax
}

function navSelect(navName){
    //console.log('about to select the correct navItem ' + navName);
    if(navName != 'empty'){
        //console.log('hitting nav change' +' '+navName);
        $('[data-nav="'+ navName +'"] .nav-item').addClass("active");
    }else{
        //console.log("Empty Nav for main");
        $(".nav-item").removeClass('active');
    }
}

bootstrap_alert = function() {}
bootstrap_alert.danger = function(message) {
    $('#alert_placeholder').html('<div class="alert alert-danger alert-dismissable"><span>'+message+'</span><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button></div>')
}

bootstrap_alert.warning = function(message) {
    $('#alert_placeholder').html('<div class="alert alert-warning alert-dismissable"><span>'+message+'</span><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button></div>')
}

bootstrap_alert.info = function(message) {
    $('#alert_placeholder').html('<div class="alert alert-info alert-dismissable"><span>'+message+'</span><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button></div>')
}

bootstrap_alert.success = function(message) {
    $('#alert_placeholder').html('<div class="alert alert-success alert-dismissable"><span>'+message+'</span><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button></div>')
}

function bootstrapAlert( type, message){
	switch(type){
		case 'warning':
			bootstrap_alert.warning(`${message}`)
       		$("#alert_placeholder").fadeTo(1000, 500).slideUp(200, function(){
        		$("#success-alert").alert('close');
	   		});
			 break;
		case 'success':
			bootstrap_alert.success(`${message}`)
       		$("#alert_placeholder").fadeTo(3000, 500).slideUp(500, function(){
        		$("#success-alert").alert('close');
	   		});
			 break;
		case 'info':
			bootstrap_alert.info(`${message}`)
       		$("#alert_placeholder").fadeTo(4000, 500).slideUp(500, function(){
        		$("#success-alert").alert('close');
	   		});
		 break;
		
		case 'danger':
			bootstrap_alert.danger(`${message}`)
       		$("#alert_placeholder").fadeTo(4000, 500).slideUp(500, function(){
        		$("#success-alert").alert('close');
	   		});
		 break;		
	}
}

function getQueryVariable(variable)
{
       var query = window.location.search.substring(1);
       var vars = query.split("&");
       for (var i=0;i<vars.length;i++) {
               var pair = vars[i].split("=");
               if(pair[0] == variable){
				   return pair[1];  //return variable for parameter
			   }
       }
       return(false);
}
		

$(document).ready(function() {
	
	
	var navName = window.location.pathname.replace("/","");
	$('.nav-item').removeClass("active");
	if(navName == '' || navName == 'index') navName = 'index';
	$('[data-nav="'+ navName +'"]').addClass("active");
	console.log(navName);
});




