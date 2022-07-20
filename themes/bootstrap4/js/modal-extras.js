$(function(){
	$(".modal.variable").on("show.bs.modal", function (event) {
		var button = $(event.relatedTarget);
		var modal = $(this);
		modal.find(".variable-content").each(function(){
			$(this).html(button.data($(this).data("variable")));
		});
		modal.find(".variable-attribute").each(function(){
			$(this).attr($(this).data("var-attr"), button.data($(this).data("variable")));
		});
		modal.find(".variable-value").each(function(){
			$(this).val(button.data($(this).data("variable")));
		});
	});

	$(".modal.auto-open").modal("show");
});
