jQuery(document).ready(function($) {

	//test ftp connection
	
	$("a.test_ftp_connection").click(function() {
		
		var ftp_host = $('#ftp_host').val();
		var ftp_username = $('#ftp_username').val();
		var ftp_password = $('#ftp_password').val();
		var nonce = $('#nonce').val();
		
		if(ftp_host == '' || ftp_username == '' || ftp_password == '') {
			alert("Please fill the all ftp details");
			return false;
		}
		
		$("#ftp_connection_status").html("Connecting..");
		
		var plugin_url = $("#form_ajaxurl").val();
		$.ajax({
			type: 'POST',
			url: plugin_url,
			data: { action: 'my_action', ftp_host: ftp_host, ftp_username: ftp_username, ftp_password: ftp_password, nonce: nonce },
			success: function(response) {  
				if(response == 1) {
					$("#ftp_connection_status").html('<span style="color:green">Ftp Connected</span>');
					$("#ftp-connection-status").val("1");
					
					
				} else {
					$("#ftp_connection_status").html('<span style="color:red">Ftp Not Connected</span>');
					$("#ftp-connection-status").val("0");
					
				}
			}
		});
	});
	
	//test db connection
	$("a.test_db_connection").click(function() {
		
		var db_name = $('#db_name').val();
		var db_host = $('#db_host').val();
		var db_username = $('#db_username').val();
		var db_password = $('#db_password').val();
		var nonce = $('#nonce').val();
		var plugin_url = $("#form_ajaxurl").val();
		
		if(db_name == '' || db_host == '' || db_username == '' || db_password == '') {
			alert("Please fill the all database details");
			return false;
		}
		
		$("#db_connection_status").html("Connecting..");
		
		$.ajax({
			type: 'POST',
			url: plugin_url,
			data: { action: 'my_action', db_name: db_name, db_host: db_host, db_username: db_username, db_password: db_password, nonce: nonce },
			success: function(response) {
				if(response == 1) {
					$("#db_connection_status").html('<span style="color:green">Database Connected</span>');
					$("#db-connection-status").val("1");
					return true;
				} else {
					$("#db_connection_status").html('<span style="color:red">Database Not Connected</span>');
					$("#db-connection-status").val("0");
					
				}
			}
		});
	});
	
	//action on connection submit button
	$("#submit_connection_form").click(function() { 
		
		var ftp_connection_status = $("#ftp-connection-status").val();
		var db_connection_status = $("#db-connection-status").val();
		if(ftp_connection_status == 1 && db_connection_status == 1) {
			return true;
		} else {
			$("a.test_ftp_connection").trigger("click");
			$("a.test_db_connection").trigger("click");
			return false;
		}
		
	});
	

});