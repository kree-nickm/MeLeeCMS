document.addEventListener("DOMContentLoaded", function(event){
   MeLeeCMS.ajaxXSLT = async function(process, inputs={}, xsl=[])
   {
      let ajaxXSLT = await fetch("", {
         method: "POST",
         body: JSON.stringify({
            process,
            inputs,
            xsl,
         }).replace(/&/g, "＆"), // TODO: Ampersands will trigger mod_security if it's using OWASP ModSecurity Core Ruleset. This is a band-aid solution, should find a better one. Note that parts of the JSON code might also match some rules, e.g. any property that starts with "profile" will trigger the ".profile" rule.
         headers: {
            'Content-Type': "application/json",
         },
      });
      let html = await ajaxXSLT.text();
      let remote_document = new DOMParser().parseFromString(html, "text/html");
      return remote_document;
   };
});
