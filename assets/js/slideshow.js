function refresh(element){
	nextSlide(element);
}

function activateSlide(element, nbSlide){
	var nbSlides = $(element).children(".container").children(".slide").length;
	$(element).attr("currentSlide", nbSlide);

	$(element.children(".container")).animate({
		right: (100*(nbSlide)).toString()+"%"
	}, 500);

	$(element).parent().find(".slideshow-points .sliderPoint").removeClass("act");
	$(element).parent().find(".slideshow-points .sliderPoint:nth-child("+(nbSlide+1)+")").addClass("act");
}

function previousSlide(element){
	var nbSlides = $(element).children(".container").children(".slide").length;
	var currentSlide = parseInt($(element).attr("currentSlide"));
	if(currentSlide == 0)
		currentSlide = nbSlides;
	activateSlide(element, currentSlide-1);

}

function nextSlide(element){
	var nbSlides = $(element).children(".container").children(".slide").length;
	var currentSlide = parseInt($(element).attr("currentSlide"));
	if(currentSlide >= nbSlides - 1)
		currentSlide = -1;
	activateSlide(element, currentSlide+1);
}

$.fn.slideshow = function(){
	var nbSlides = $(this).children(".container").children(".slide").length;
	var element = $(this);
	$(this).children(".container").children(".slide").css("width", (100/nbSlides).toString() + "%");
	$(this).children(".container").css("width", (100*nbSlides).toString() + "%");
	$(this).children(".container").css("right", "0%");
	$(this).attr("nbSlides", nbSlides);
	$(this).attr("currentSlide", 0);


	$(document).ready(function(){

		$(element).wrap('<div class="slideshow-container"></div>')
		if($(element).hasClass('slideshow-arrows')){
			$(element).parent().append('<a class="control arrowLeft blueLeftIcon">&nbsp;</a><a class="control arrowRight blueRightIcon">&nbsp;</a>');

			$(element).parent().find('.arrowLeft').click(function(){
				previousSlide(element);
			});

			$(element).parent().find('.arrowRight').click(function(){
				nextSlide(element);
			});
		}

		if($(element).hasClass('slideshow-points')){
			var strPoints = '<div class="sliderPoint act"></div>';
			for(var i = 1; i < nbSlides; i++){
				strPoints = strPoints + '<div class="sliderPoint"></div>';
			}
			$(element).parent().append('<div class="center"><div class="slideshow-points">'+strPoints+'</div></div>');
			$(element).parent().find('.sliderPoint').click(function(){
				activateSlide(element, ($(this).index()));
			});
		}

		//dont autoscroll if arrow exists 
		if($(element).find(".arrowRight").length == 0 || $(element).hasClass("autoslide")){
			var defaultDelay = 7000;
			if(typeof $(element).attr("data-delay") != "undefined"){
				defaultDelay = parseInt($(element).attr("data-delay"));
			}
			window.setInterval(function(){refresh(element);}, defaultDelay);
		}
		activateSlide(element, 0);
	});
}

$(document).ready(function(){
	$('.slideshow').each(function(){
		$(this).slideshow();
	});
});
