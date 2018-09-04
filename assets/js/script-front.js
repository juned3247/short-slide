jQuery( document ).ready( function() {
	jQuery('.slideshow').slick({
		dots: true,
		infinite: true,
		speed: 500,
		slidesToShow: 1,
		slidesToScroll: 1,
		autoplay: true,
		autoplaySpeed: 2000,
		adaptiveHeight: true
	});
});