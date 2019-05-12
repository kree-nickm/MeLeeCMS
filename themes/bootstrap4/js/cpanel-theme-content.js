jQuery(function(){
	$("#editor_container").parent().resizable({
		//alsoResize: $("#editor_container").parent().parent(),
		//autoHide: true,
		handles: "s",
		resize: function(resizeEvent, ui){
			$(ui.element).parent().width(ui.size.width);
		},
	});
	$("#load_file_btn").on("changed.bs.select", function(changeEvent){
		$("#file_name").text($(this).val());
		var existing = $("#"+ $(this).val().replace(".", "_"));
		if(existing.length)
		{
			showFileEditor.call(existing.next());
		}
		else
		{
			jQuery.ajax({
				url: "ajax.php",
				dataType: "text",
				success: selectFileResponse,
				context: this,
				method: "POST",
				data: {
					theme: $(this).data("theme"),
					file: $(this).val(),
				},
			});
		}
	});
	
	function selectFileResponse(data, textStatus, jqXHR)
	{
		var id = $(this).val().replace(".", "_");
		var textarea = document.createElement("textarea");
		$(textarea).val(data).attr("id", id);
		$("#editor_container").append(textarea);
		if($(this).val().substr(-4) == ".css")
		{
			var editor = CodeMirror.fromTextArea(textarea, {mode:'css',lineNumbers:true,viewportMargin:Infinity,lineWrapping:true});
		}
		else if($(this).val().substr(-3) == ".js")
		{
			var editor = CodeMirror.fromTextArea(textarea, {mode:'javascript',lineNumbers:true,viewportMargin:Infinity,lineWrapping:true});
		}
		else if($(this).val().substr(-4) == ".xsl")
		{
			var editor = CodeMirror.fromTextArea(textarea, {mode:'xml',lineNumbers:true,viewportMargin:Infinity,lineWrapping:true});
		}
		showFileEditor.call($(textarea).next());
	}
	
	function showFileEditor()
	{
		$(this).show().siblings().hide();
	}
});