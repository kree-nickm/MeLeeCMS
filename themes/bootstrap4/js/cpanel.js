$(function(){
   /*************************************************
   **************** Settings Page *******************
   *************************************************/
   function toggleSettingButtons(container)
   {
      let input = $(container).find(".js-value[name]");
      let value = input.val();
      let initialValue = input.data("saved");
      let defaultValue = input.data("default");
      $(container).find(".js-save").attr("disabled", value == initialValue);
      $(container).find(".js-reset").attr("disabled", value == defaultValue);
   }
   $(".js-setting").each((id,elem) => toggleSettingButtons(elem));
   
   $(".js-setting .js-value[name]").change(event => toggleSettingButtons($(event.target).parents(".js-setting")));
   $(".js-setting .js-value[name]").on("input", event => toggleSettingButtons($(event.target).parents(".js-setting")));
   
   $(".js-setting .js-reset").click(function(event){
      let parent = $(event.target).parents(".js-setting");
      let input = parent.find(".js-value[name]");
      input.val(input.data("default"));
      if(input.selectpicker)
         input.selectpicker('refresh');
      toggleSettingButtons(parent);
   });
   
   $(".js-setting .js-save").click(function(event){
      let parent = $(event.target).parents(".js-setting");
      let input = parent.find(".js-value[name]");
      let setting = input.attr("name");
      fetch("", {
         method: "POST",
         body: "AJAX=true&adminSaveSettings=1&setting="+ encodeURIComponent(setting) +"&value="+ encodeURIComponent(input.val()),
         headers: {'Content-Type': "application/x-www-form-urlencoded"},
      }).then(async response => {
         let json = await response.json();
         if(json?.adminSaveSettings?.settings?.[setting])
         {
            input.val(json.adminSaveSettings.settings[setting]);
            input.data("saved", json.adminSaveSettings.settings[setting]);
            if(input.selectpicker)
               input.selectpicker('refresh');
            toggleSettingButtons(parent);
         }
      }, response => {
         console.error("Failed to update setting:", response);
      });
   });
});
