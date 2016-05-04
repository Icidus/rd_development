/**
 * 
 */

  jQuery(document).ready(function(){
	  
	  function sendCannedMessage(event, ui){
		  var target = jQuery('.messageTarget',event.srcElement).html();
		  //var targetParams = getParams(target, '{', '}');
		  jQuery('#mxeResource').val(target);
		  var payloadText = jQuery('.payload',event.srcElement).html();
		  var sourceMessage = html_entity_decode(payloadText ? payloadText : '');
		  jQuery('#mxeMessage').html(sourceMessage);
		  //var messageParams = getParams(sourceMessage, '[[',']]');
	  }
	  
	  jQuery('#cannedMessages p').draggable({
		  revert: true,
		  revertDuration: 1000,
		  snap: '#mxeMessage',
		  snapMode: 'inner',
		  zIndex: 2000
	  });
	  jQuery('#cannedMessages p').bind('dblclick', function(event, ui){
		      jQuery(this).css('background-color', '#ff9');
		      jQuery(this).animate({'background-color' : '#fff'}, 2000);
		      sendCannedMessage(event,ui);
	      }
	  );
	  jQuery('#mxeMessage').droppable({
		  activeClass: 'activeDragTarget',
		  accept: '#cannedMessages p',
		  tollerance: 'touch',
		  drop: sendCannedMessage
	  });
	  
	  function successFunction(data){
		  if (data.hasOwnProperty('success') && data.success) {
			  jQuery('#mxeResponse').val(data.message.raw);
		  } else {
			  alert('The MXE service failed: \n' 
					  + (data.hasOwnProperty('errorType') ? data.errorType : 'RD_UNSPECIFIED_ERROR')
					  + ': \n'
					  + (data.hasOwnProperty('message') ? data.message : 'No Message')
			  );
		  }
		 
	  }
	  
	  function failFunction(data){
		  alert('something went wrong :[');
	  }
	  
	  function fireMahLazer(nodeThis, event){
		  var messageToSend = jQuery.trim(jQuery(nodeThis).val());
		  if ('' == messageToSend) {
			  jQuery.ajax({
			      url : 'json.php/mxe/' + jQuery('#mxeResource').val(),
				  dataType : 'json', 
				  error : failFunction,
				  success : successFunction
		  	  });
		  } else {
			  jQuery.ajax({
			      type : 'POST',
			  	  url : 'json.php/mxe/' + jQuery('#mxeResource').val(),
				  data : messageToSend,
				  contentType: 'text/xml',
			  	  dataType : 'json', 
				  error : failFunction,
				  success : successFunction
		  	  });
		  }
	  }
	  
	  jQuery('#mxeMessage').bind('keydown',function(event){
		  if (event.which == 13 && event.ctrlKey) {
			  fireMahLazer(this,event);
			  return false;
		  } else {
			  return true;
		  }
		  
	  });
	  jQuery('#submitMessage').bind('click',function(event){
		  fireMahLazer(jQuery('#mxeMessage'),event);
	  });
	  jQuery('#mxeIntegrationTest').bind('submit', function(){
		  fireMahLazer(jQuery('#mxeMessage'),event);
		  return false;
	  });
  });