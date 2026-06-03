(function() {
   'use strict';

   $(function() {
      const rootDoc = (window.CFG_GLPI && window.CFG_GLPI.root_doc) ? window.CFG_GLPI.root_doc : '';
      const pluginUrl = rootDoc + '/plugins/atribuicaointeligente/front/available_users.ajax.php';

      $.ajaxPrefilter(function(options) {
         if (!options || typeof options.url !== 'string') {
            return;
         }

         if (!options.url.includes('/ajax/getDropdownUsers.php')) {
            return;
         }

         const data = normalizeData(options.data);
         if (!data || data.right !== 'own_ticket') {
            return;
         }

         options.url = pluginUrl;
      });
   });

   function normalizeData(data) {
      if (!data) {
         return {};
      }

      if (typeof data === 'string') {
         const parsed = {};
         data.split('&').forEach(function(part) {
            const pair = part.split('=');
            if (pair.length >= 2) {
               parsed[decodeURIComponent(pair[0])] = decodeURIComponent(pair.slice(1).join('='));
            }
         });
         return parsed;
      }

      if (typeof URLSearchParams !== 'undefined' && data instanceof URLSearchParams) {
         const parsed = {};
         data.forEach(function(value, key) {
            parsed[key] = value;
         });
         return parsed;
      }

      return data;
   }
})();
