import $ from 'jquery';
const hasJustOpened = false;

$(document).ready(function(){
	$(".openPopup").click(function(){
		$("#"+$(this).attr("rel")).show();
		openPopup();
		return false;
	});

	$(".closePopup").click(function(){
		$(".popup").hide();
		closePopup();
		return false;
	});

	$("body").click(function(){
		if($("#popups").hasClass("opened") && !hasJustOpened){
			$(".popup").hide();
			closePopup();
		}
	});

	$("#popups").click(function(e){
		 e.stopPropagation();
	});

	$(".popup-opened").each(function(){
		openPopup($(this), true);
	});
});


$(window).scroll(function() {
    clearTimeout($.data(this, "scrollTimer"));
    $.data(this, "scrollTimer", setTimeout(function() {
        refreshPopupPlace();
    }, 200));
});

$(window).resize(function() {
	refreshPopupPlace();
});

window.refreshPopupPlace = function refreshPopupPlace(){
	const scroll = $(document).scrollTop();
	const windowHeight = $(window).height();
	const $popups = $("#popups");
	const popupHeight = parseInt($popups.height()) + parseInt($popups.css("padding-top")) + parseInt($popups.css("padding-bottom"));
	const newTop = (windowHeight/2 - popupHeight/2 + scroll).toString() + "px";
	// $('#popups').css("top", newTop);
	$popups.animate({
		top: newTop
	}, 200);
}

window.openPopup = function openPopup(popup, dontAnimate){
	if(typeof dontAnimate === "undefined"){
		dontAnimate = false;
	}
	if(typeof popup !== "undefined"){
		$(popup).show();
	}

	refreshPopupPlace();
	const $popups = $("#popups");
	if(!$popups.hasClass("opened")){

		$popups.css('display', 'block');
		$popups.animate({
			opacity: 1
		}, 400);
		$popups.addClass("opened");

		const popupsOverlay = $("#popups-overlay");
		popupsOverlay.css("display", "block");

		if(!dontAnimate){	
			popupsOverlay.animate({
				opacity: 1
			}, 400);	
		}
		else{
			popupsOverlay.css("opacity", 1);
		}
	}

	//resize if map on the popup
	if(window.notifmap_container !== undefined){
		google.maps.event.trigger(window.notifmap_container.map, "resize");
	}
}

window.closePopup = function closePopup(){
	const $popups = $("#popups");
	if($popups.hasClass("opened") && $popups.css("opacity") === "1"){
		$popups.animate({
			opacity: 0
		}, 400, function(){
			$popups.css("display", "none");
			$popups.removeClass("opened");
		});
		const $popupsOverlay = $("#popups-overlay");
		$popupsOverlay.animate({
			opacity: 0
		}, 400, function(){
			$popupsOverlay.css("display", "none");
		});	
	}
}
