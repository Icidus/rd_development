var videoUploadManager = function()
{
	var existingSelection = null;
	var existingFileSection = null;
	var removeExistingButton = null;
	var uploadedFileInput = null;
	var uploader = null;
	
	function deleteFileHash(hash)
	{
		if( '' != hash){
			jQuery.ajax('json.php/fileupload/delete/' + hash + '/',{
				'dataType' : 'json',
				'error' : function (jqXHR, textStatus, errorThrown) {
					var test = '';
				},
				'success' : function (data){
					if (!data.hasOwnProperty('success') || !data.success) {
						alert('Unable to delete the selected file: ' + (data.hasOwnProperty('error') ? data.error : 'No reaosn given.'));
					} else {
						if (existingSelection) {
							existingSelection.val('');
							jQuery('[value="' + hash + '"]',existingSelection).remove();
							updateSelectFile();
							jQuery('.qq-upload-list').children().remove();
							jQuery('#fileUploader .qq-upload-button .buttonText').html('Upload a file');
			        		jQuery('#fileUploader .qq-upload-button').removeClass('disabledButton');
							uploader.enable();
						}
						if (uploadedFileInput) {
							uploadedFileInput.val('');
							uploadedFileInput.remove();
							uploadedFileInput = null;
							uploader.enable();
						}
						if (existingFileSection) {
		        			existingFileSection.show().prev().show();	
		        		}
					}
				}
			}); 
		}
	}
	
	function removeUploadedFile()
	{
		var existingFileValue = (uploadedFileInput ? uploadedFileInput.val() : existingSelection.val());
		deleteFileHash(existingFileValue);
	}
	
	function removeSelectedFile()
	{
		var existingFileValue = existingSelection.val();
		deleteFileHash(existingFileValue);
	}
	
	function updateSelectFile()
	{
		var existingFileValue = existingSelection.val();
		if( '' != existingFileValue && existingFileValue){
			jQuery('#fileUploader').hide().prev().hide();
			jQuery('#removeExistingButton').show();
		} else {
			jQuery('#fileUploader').show().prev().show();
			jQuery('#removeExistingButton').hide();
		}
	}
	
	jQuery(document).ready(function()
	{
		existingSelection = jQuery('#existingFileHash');
		removeExistingButton =  jQuery('#removeExistingButton');
		if(existingSelection) {
			existingSelection.live('change', updateSelectFile);
			removeExistingButton.live('click keydown', removeSelectedFile);
		}
		
		updateSelectFile();
	});
	
	return {
		createUploader : function(){            
		    uploader = new qq.FileUploader({
		        element: document.getElementById('fileUploader'),
		        action: 'json.php/fileupload/',
		        debug: true,
		        onProgress: function(id,fileName, loaded, total)
		        {
					var blah = loaded;
		        },
		        onComplete: function(id, fileName, response)
		        {
		        	if (response.success) {
		        		jQuery('#fileUploader .qq-upload-button .buttonText').html('Uploaded');
		        		existingFileSection = jQuery('#fileChooser');
		        		if (existingFileSection) {
		        			existingFileSection.hide().prev().hide();
		        			//var existingSelection = jQuery('#existingFileHash');
		        			existingSelection.val('');
		        			existingSelection.append('<option value="' 
		        					+ response.fileReferenceHash 
		        					+ '" selected="selected">Uploaded file, ' 
		        					+ response.originalFilename 
		        					+ '</option>');		
		        		} else {
		        			jQuery('#fileUploader').append('<input name="existingFileHash" type="hidden" value="' + response.fileReferenceHash  + '">');
		        			var uploadedFileInput = jQuery('#existingFileHash');
		        		}
		        		jQuery('#fileUploader .qq-upload-success').append(
		        		'<div id="removeUploadedButton" tabindex="-1" class="qq-upload-button longer" style="float:right;display:inline-block;">Upload a different file</div><div class="clear"></div>'		
		        		);
		        		var removeUploadedButton = jQuery('#removeUploadedButton');
		        		//var removeExistingButton =  jQuery('#removeUploadedButton');
		        		removeUploadedButton.live('click keydown', removeUploadedFile);
		        	} else {
		        		jQuery('#fileUploader .qq-upload-button .buttonText').html('Upload a file');
		        		jQuery('#fileUploader .qq-upload-button').removeClass('disabledButton');
		        		uploader.enable();
		        	}
		        	
		        },
		        onSubmit: function(id,fileName)
		        {
					jQuery('#fileUploader .qq-upload-button .buttonText').html('Uploading');
					jQuery('#fileUploader .qq-upload-button').addClass('disabledButton');
					uploader.disable();
		        }
		    }); 
		    jQuery('#fileUploader').append('<div class="clear">&nbsp;</div>');        
		}
	}
}();
