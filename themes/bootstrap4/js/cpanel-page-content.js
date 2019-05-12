jQuery(function(){
	var script_name = "cpanel-page-content.js";
	var script_filepath = jQuery("script[src*='"+ script_name +"']").attr("src");
	var theme_dir = script_filepath.substr(0, script_filepath.indexOf(script_name)-3);
	
	jQuery(".add-page-content button").click(addContent);
	jQuery(".content-property .add-content button").click(addContent);
	jQuery(".remove-content").click(removeContent);
	jQuery(".move-content-up").click(moveContentUp);
	jQuery(".move-content-down").click(moveContentDown);
	jQuery(".add-dict").click(addToDictionary);
	jQuery(".remove-dict").click(removeFromDictionary);
	jQuery("#form_edit_page").on("submit", submitSavePage);
	
	jQuery(".modal.db-config").on("show.bs.modal", function (event) {
	});
	
	
	function addContent(clickEvent)
	{
		var findParent = jQuery(this).parents(".add-page-content");
		if(findParent.length > 0)
		{
			var context = jQuery("#page_content_container");
			var contentClass = findParent.find("select").val();
		}
		else
		{
			var context = jQuery(this).parents(".page-content").first().find(".content-container").first();
			var contentClass = jQuery(this).parents(".add-content").find("select").val();
		}
		jQuery.ajax({
			url: "ajax.php",
			dataType: "html",
			success: addContentXMLResponse,
			context: context,
			method: "POST",
			data: {
				ContentClass: contentClass,
				idPrefix: context.data("id-prefix"),
				namePrefix: context.data("name-prefix"),
			},
			cache: false,
		});
	}
	
	function addContentXMLResponse(data, textStatus, jqXHR)
	{
		var card = jQuery(this).append(data).children().last();
		card.find(".content-property .add-content button").click(addContent);
		card.find(".remove-content").click(removeContent);
		card.find(".move-content-up").click(moveContentUp);
		card.find(".move-content-down").click(moveContentDown);
		card.find(".add-dict").click(addToDictionary);
		//console.log(card.find("*[id]"), card.find("*[name]"));
	}
	
	function removeContent(clickEvent)
	{
		jQuery(this).parents(".page-content").first().remove();
	}
	
	function moveContentUp(clickEvent)
	{
		var card = jQuery(this).parents(".page-content").first();
		card.insertBefore(card.prev());
	}
	
	function moveContentDown(clickEvent)
	{
		var card = jQuery(this).parents(".page-content").first();
		card.insertAfter(card.next());
	}
	
	function addToDictionary(clickEvent)
	{
		// TODO: Should this be moved to PHP and handled with AJAX? This is currently the only place in the CMS where XSLT is handled by JavaScript. Plus, client-side XSLT brings up lots of annoying caching problems with xsl:include that are extremely tedious to deal with.
		var xsl = jQuery.parseXML('<?xml version="1.0"?><xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"><xsl:output method="html"/><xsl:template match="/dummy"><xsl:call-template name="dictionary_entry"><xsl:with-param name="id_prefix" select="@id_prefix"/><xsl:with-param name="name_prefix" select="@name_prefix"/><xsl:with-param name="id" select="@id"/><xsl:with-param name="name" select="@name"/></xsl:call-template></xsl:template><xsl:include href="'+ theme_dir +'templates/cpanel-content.xsl"/></xsl:stylesheet>');
		var xsltProcessor = new XSLTProcessor();
		xsltProcessor.importStylesheet(xsl);
		jQuery(this).parent().find(".dictionary-container").append(xsltProcessor.transformToFragment(jQuery.parseXML('<?xml version="1.0"?><dummy id="'+ jQuery(this).data("content-id") +'" name="'+ jQuery(this).data("prop-name") +'" id_prefix="'+ jQuery(this).data("id-prefix") +'" name_prefix="'+ jQuery(this).data("name-prefix") +'"></dummy>'), document)).children().last().find(".remove-dict").click(removeFromDictionary);;
	}
	
	function removeFromDictionary(clickEvent)
	{
		jQuery(this).parents(".dictionary-entry").remove();
	}
	
	function submitSavePage(submitEvent)
	{
		//submitEvent.preventDefault();
		var content_count = [0];
		var id_nest = [];
		var last_key = null;
		for(var i in this.elements)
		{
			switch(this.elements[i].name)
			{
				case "permissions[]":
				case "page_css[]":
				case "page_js[]":
				case "page_xsl[]":
					if(this.elements[i].value == "")
					{
						var none = document.createElement("option");
						none.setAttribute("value", "");
						none.setAttribute("selected", "selected");
						this.elements[i].appendChild(none);
					}
					break;
				case "content[][content_id]":
					if(this.elements[i].value != "")
						id_nest = [this.elements[i].value];
					else
						id_nest = [++content_count[0]];
					this.elements[i].name = "";
					last_key = null;
					break;
				default:
					if(this.elements[i].name && this.elements[i].name.indexOf("content[]") == 0)
					{
						
						this.elements[i].name = "page_content["+ id_nest[0] +"]"+ this.elements[i].name.substr(9);
						var a;
						// Handle dictionaries.
						if(this.elements[i].name.substr(-7) == "[][key]")
						{
							if(this.elements[i].value != "")
								last_key = this.elements[i].value;
							else
								last_key = null;
							this.elements[i].name = "";
						}
						else if((a = this.elements[i].name.indexOf("[][value]")) != -1)
						{
							if(last_key != null)
							{
								this.elements[i].name = this.elements[i].name.substr(0, a) +"["+ last_key +"]";
								last_key = null;
							}
							else
								this.elements[i].name = ""; // Should report an error to the user for filling out a value with no key.
						}
						// Handle container content
						var depth = 1;
						while((a = this.elements[i].name.indexOf("[content][]")) != -1)
						{
							if(isNaN(content_count[depth])) content_count[depth] = 0;
							if(this.elements[i].name.substr(a+11, 12) == "[content_id]")
							{
								if(this.elements[i].value != "")
									id_nest[depth] = this.elements[i].value;
								else
									id_nest[depth] = ++content_count[depth];
								this.elements[i].name = "";
								last_key = null;
							}
							else
							{
								this.elements[i].name = this.elements[i].name.substr(0, a+10) + id_nest[depth] + this.elements[i].name.substr(a+10);
							}
							depth++;
						}
					}
			}
		}
		//console.log(this.elements);
	}
});