class OAuth2Client
{
   static init()
   {
      // Twitch API (and possibly others) sends implicit grants as a hash, specifically so they don't reach a server, but we need them on our server, so...
      let hashParams = location.hash.substr(1).split("&");
      let hashObject = {};
      for(let p of hashParams)
      {
         let parts = p.split("=");
         if(parts.length == 2)
            hashObject[parts[0]] = parts[1];
      }
      if(hashObject.access_token && hashObject.scope && hashObject.state && hashObject.token_type)
      {
         // Need to reload the page but include the parameters in the query so PHP can read them. Not sure if we should make a special page for this, or just plain redirect it as though the API had sent a query instead of a fragment.
         location.href = `${location.origin}${location.pathname}?implicit_grant=true&access_token=${hashObject.access_token}&scope=${hashObject.scope}&state=${hashObject.state}&token_type=${hashObject.token_type}`;
      }
      else
      {
         document.addEventListener("DOMContentLoaded", async function(event){
            if(MeLeeCMS?.data?.custom?.oauth2?.token?.access_token)
            {
               // Now the actual meat of the code.
               MeLeeCMS.OAuth2Client = new OAuth2Client(MeLeeCMS.data.custom.oauth2);
               // Testing code for Twitch API only.
               console.log("API user test:", await MeLeeCMS.OAuth2Client.apiRequest("/helix/users", "GET"));
            }
            else
            {
               console.warn("OAuth2Client settings were not correctly passed to MeLeeCMS JavaScript. Don't load this JavaScript file if you don't need it.");
            }
         });
      }
   }
   
   static objectToPostData(object)
   {
      let out = "";
      for(let key in object)
      {
         out = out + "&" + encodeURIComponent(key) + "=" + encodeURIComponent(object[key]);
      }
      return out.slice(1);
   }
   
   constructor(options)
   {
      this.auth_url = options.auth_url;
      this.api_url = options.api_url;
      this.client_id = options.client_id;
      this.scope = options.scope;
      this.redirect_uri = options.redirect_uri;
      this.access_token = options.token.access_token;
      this.token_type = options.token.token_type.charAt(0).toUpperCase() + options.token.token_type.slice(1);
      this.state = "implicit_relog";
      this.log = [];
   }
   
   relog()
   {
      let url = `${this.auth_url}/oauth2/authorize?response_type=token&client_id=${this.client_id}&scope=${encodeURIComponent(this.scope.join(" "))}&redirect_uri=${encodeURIComponent(this.redirect_uri)}&state=${encodeURIComponent(this.state+location.pathname+location.search)}`;
      
      // Option A is to go through the implicit grant process again with the current page. Downside is the user will notice the big multi-page request chain as their user-token refreshes. Since this is being called without warning when the access token expires, it might take them by surprise.
      location.href = url;
      
      // Option B is to use AJAX to go through the process, which should redirect back to this site. Then this site can update the implicit token session var, and then once that's done we can reload the current page, which should have that new token because of the session. Upside is it would generally happen seamlessly without the user noticing, but some APIs don't seem to like that this is happening and are returning errors, so maybe it's not possible?
      /*return fetch(url, {mode:"no-cors"}).then(response => {
         console.log("Login request response:", response);
      }, response => {
         console.error("Login request rejected:", response);
      });*/
   }
   
   apiRequest(url, method="GET", data={}, headers={})
   {
      headers['Client-Id'] = this.client_id;
      headers['Authorization'] = this.token_type +" "+ this.access_token;
      let fetchInit = {
         method: method,
         // TODO: Not sure OAuth2 APIs want JSON, might have to convert to application/x-www-form-urlencoded
         body: JSON.stringify(data),
         headers: headers,
         //mode: "no-cors",
      };
      if(method=="GET" || method=="HEAD")
         delete fetchInit.body;
      else
         headers['Content-Type'] = "application/json";
      return fetch(this.api_url + url, fetchInit).then(response => {
         // TODO: These tokens expire without warning. Add code to refresh them when that happens.
         console.log("API request response:", response);
         // Cannot get rate limits in JavaScript because of Twitch's incompetence (what else is new), so just have to hope we don't hit it.
         //console.log("Rate limit:", response.headers.get('ratelimit-remaining'), response.headers.get('ratelimit-limit'), response.headers.get('ratelimit-reset'));
         this.log.push(response);
         return response.json();
      }, response => {
         console.error("API request rejected:", response);
         this.log.push(response);
         return response;
      });
   }
}
OAuth2Client.init();
