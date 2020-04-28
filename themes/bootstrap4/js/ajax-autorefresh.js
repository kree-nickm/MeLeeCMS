$(function(){
	// Ideally 'invertal' should be specified dynamically.
	let interval = 1000*10*60;
	let timer;
	function refresh()
	{
		clearTimeout(timer);
		$(".ajax-autorefresh").each(function(id,elem){
			$(elem).parent().load(" #"+elem.id);
		});
		timer = setTimeout(refresh, interval);
	}
	timer = setTimeout(refresh, interval);
});