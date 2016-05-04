if(!ncsuLib){
	var ncsuLib = {catalog:{}};
} else if (!ncsuLib.catalog){
	ncsuLib.catalog = {};
}

ncsuLib.catalog.quickView = function(){
	var externalResoruces = false;
	var quickViewIsbns = {};
	var quickViewCurrentIsbn = '';
	var gbInfo = {};
	var quickViewHost = (contextPath ? contextPath : '/catalog/');
	var targetHeight = 600;
	var targetWidth = 800;
	var animationSpeed = 250;
	var modalBody = false;
	var maxCoverWidth = 130;
	var locked = false;
	
	var closerFunction = null;
	
	function defaultCloserFunction(){
		$('body').removeClass('modalActive');
		$('#quickView').dialog('close');
		// in case it is still around (generated closers should have already done this)
		destroyCloser();
	}

	function generateShrinkingCloserFunction(targetNode){		
		return function(){
			if(targetNode.length > 0){
				var targetWidth = $('div',targetNode).width();
				var targetHeight = $('div',targetNode).height();
				var targetOffset = $('div',targetNode).offset();
				targetOffset.top -= ($('body').hasClass('modal') ? $(window).scrollTop() : 0);
				destroyCloser();
				grow(targetOffset.left, targetOffset.top, targetWidth, targetHeight, defaultCloserFunction);
			} else {
				defaultCloserFunction();
			}
		}
	}	

	function lock()
	{
		locked = true;
    }
	
    function unlock()
    {
    	locked = false;
    }
    
    function isLocked(){
    	return locked;
    }
	
	function processGBInfo(gbResult)
	{
	    var theBook = gbResult[quickViewCurrentIsbn];
	    if ( theBook ) { gbInfo = theBook; }
		if ( gbInfo.preview && (gbInfo.preview == "full" || gbInfo.preview == "partial") && gbInfo.preview_url ) {
		    $("a.googleBookPreview").attr("href", gbInfo.preview_url).show();
		}
	}
	
	function calculateCenter(width, height){
		return {
			left: Math.floor(($(window).width() / 2) - (width / 2)),
			top: (Math.floor(($(window).height() / 2) + $(window).scrollTop()) - (height / 2))
		};
	}
	
	function openQuickView(catkey)
	{
		if(isLocked()){
			return false;
		}
		lock();
		var eventSource = $('#quickViewFor' + catkey);
		if(eventSource.length > 0){
			var sourceHeight = $('div',eventSource).height();
			var sourceWidth = $('div',eventSource).width();
			var sourceOffset = $('div',eventSource).offset();
			sourceOffset.top -= $(window).scrollTop();
		} else {
			var sourceHeight = targetHeight;
			var sourceWidth = targetWidth;
			var sourceOffset = calculateCenter(targetWidth, targetHeight);
		}
		$('#quickView').dialog({
			'dialogClass': 'quickViewDialog',
			'draggable': false,
			'height' : sourceHeight,
			'width' : sourceWidth,
			'resizable' : false,
			'autoOpen' : false,
			'show' : 'fade',
			'hide' : 'fade',
			'position' : [sourceOffset.left, sourceOffset.top],
			'modal' : true
		});
		quickviewCall(catkey, eventSource);
		return false;
	}
	
	function createCloser(closerFunction){
		$('body').append('<div id="closeQuickViewButton"></div>');
		$('#closeQuickViewButton').click(closerFunction);
		$('#closeQuickViewButton').hover(function(){$(this).toggleClass('hover');});
		
	}
	
	function destroyCloser(){
		$('#closeQuickViewButton').remove();
		unlock();
	}
	
	function stepCloser(centerPosition){
		var coordinates = {
			left: centerPosition.left + (targetWidth - 16),
			top: centerPosition.top - 15
		};
		$('#closeQuickViewButton').offset(coordinates);
	}
	
	function resizeCover(){
		var target = $('#quickView .cover img');
		/*var targetContainer = $('#quickViewPort .header .cover');
		var originalContainerWidth = targetContainer.css('width');
		if(originalContainerWidth){
			targetContainer.css('width', 'auto');
		}*/
		var originalHeight = target.height();
		var originalWidth = target.width();
		if(originalHeight && originalWidth){
			target.height(Math.floor(originalHeight * 0.75));
			target.width(Math.floor(originalWidth * 0.75));
			if(target.width() > maxCoverWidth) {
				var problematicWidth = target.width();
				target.width(maxCoverWidth);
				target.height(Math.floor(target.height() * (maxCoverWidth / problematicWidth)));
			}
		}
		/*if(originalContainerWidth){
			targetContainer.css('width', originalContainerWidth);
		}*/
	}
	
	function quickviewCall(catkey, eventSource)
	{
		if(eventSource){
			eventSource.addClass('loading');
		}
		$('#quickView').html('<p>Loading Title Information.</p>');
		$.ajax({
			url: quickViewHost + 'quickview/catkey/' + catkey,
			type: 'GET',
			//async: false,
			dataType: 'text',
			success: function(data){ 
				var resultingNode = $('<div>' + data + '</div>');
				var resultBodyContents = $('#quickViewPort', resultingNode);
				$('#quickView').html(resultBodyContents);
				$( ".extraInfo" ).accordion({autoHeight:false});
				//we have to make all adjustments that might effect height so we can auto-size the qv...
				$('.quickViewDialog').css('top','-9999px');
				$('.quickViewDialog').css('left','-9999px');
				$('.quickViewDialog').css('display','block');
				$('.quickViewDialog').css('visibility','visible');
				resizeCover();
				targetHeight = $('#quickViewPort').height();
				var quickViewTarget = calculateCenter(targetWidth, targetHeight);
				
				$('.quickViewDialog').addClass('growing');
				if(modalBody){
					$('body').addClass('modalActive');
				}
				createCloser(generateShrinkingCloserFunction(eventSource));
				$('#quickView').dialog('open');
				grow(quickViewTarget.left, quickViewTarget.top, targetWidth, targetHeight, function(){
					if (eventSource) {
						eventSource.removeClass('loading');
					}
					$('.quickViewDialog').removeClass('growing');
				});
				
				$('.ui-widget-overlay').click(generateShrinkingCloserFunction(eventSource));

				var quickViewCatkey = $.trim($('#quickView .quickViewCatkey').html());
				var quickViewIsbn = $.trim($('#quickView .quickViewIsbn').html());
				if (quickViewIsbn && externalResoruces) {
					quickViewIsbns[quickViewCatkey] = quickViewIsbn;
					quickViewCurrentIsbn = quickViewIsbn;
					$('body').append('<script src="http://books.google.com/books?jscmd=viewapi&bibkeys=' + quickViewIsbn + '&callback=ncsuLib.catalog.quickView.googleBooksCallback"></script>');
				} else {
					quickViewCurrentIsbn = '';
				}
				// #TODO this is a little muddled separation of concerns wise.
				if ('undefined' != typeof cartHandler) {
					$('div.cartButton').not('div.cartButton.inCartItem').click(function(){
						cartHandler.generateAddFunction(this)();
					});
					$('div.cartButton.inCartItem').click(function(){
						cartHandler.generateRemoveFunction(this)();
					});
					
				} else {
					$('div.cartButton').remove();
				}
				$('.quickViewDialog').css('scroll','none');

			},
			error : function(){
				if(eventSource){
					eventSource.removeClass('loading');
				}
				var errorDialogHtml = "<p>The catalog encountered an unexpected problem attempting to load Quick View for this item.</p>";
				var errorDialogOptions = {
					title : 'Error Message',
					dialogClass : 'errorDialog'
				};
				$(errorDialogHtml).appendTo('body').dialog(errorDialogOptions);
			}
		});	
		return false;
	}
	
	function grow(left, top, width, height, callback){
		var originalDialogOverflow = $('.quickViewDialog').css('overflow');
		var originalrootOverflow = $('#quickView').css('overflow');
		$('.quickViewDialog').css('overflow','hidden');
		$('#quickView').css('overflow','hidden');
		$('.quickViewDialog').animate({
    		top: top + 'px',
    		left: left + 'px',
    		width: width + 'px',
    		height: height + 'px'
    	}, {
    		duration:animationSpeed, 
    		complete: function() { 
    			$('.quickViewDialog').css('overflow',originalDialogOverflow);
    			$('#quickView').css('overflow',originalrootOverflow);
    			callback();
    			stepCloser($('.quickViewDialog').position());
    		}, 
    		step: function(){
    			$('#quickView').height($('.quickViewDialog').height());
    		}
    	});
	}
	
	return{
		show: openQuickView,
		googleBooksCallback: processGBInfo,
		animateToCenter: function(){
			var targetPosition = calculateCenter($('.quickViewDialog').width(),$('.quickViewDialog').height());
	    	$('.quickViewDialog').animate({
	    		top: targetPosition.top + 'px',
	    		left: targetPosition.left + 'px'
	    	}, {
	    		duration: animationSpeed, 
	    		step: function(){
	    			stepCloser($('.quickViewDialog').position());
	    		}
	    	});	
		},
		recenter: function(){
			var targetPosition = calculateCenter($('.quickViewDialog').width(),$('.quickViewDialog').height());
			$('.quickViewDialog').offset(targetPosition);
			stepCloser(targetPosition);
		},
		lock: lock,
		unlock: unlock,
		isLocked: isLocked
	};
}();

//$(document).ready(ncsuLib.catalog.quickView.init);