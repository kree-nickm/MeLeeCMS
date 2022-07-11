document.addEventListener("DOMContentLoaded", function(event){
   MeLeeCMS.ajaxXSLT = async function(process, inputs={}, xsl=[])
   {
      let ajaxXSLT = await fetch("ajaxXSLT.php", {
         method: "POST",
         body: JSON.stringify({
            process,
            inputs,
            xsl,
         }),
         headers: {
            'Content-Type': "application/json",
         },
      });
      let html = await ajaxXSLT.text();
      let remote_document = new DOMParser().parseFromString(html, "text/html");
      return remote_document;
   };
});
