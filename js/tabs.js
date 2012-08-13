// Tabs with resize and history
jQuery(document).ready(function() {

   // Lib handle the history
   jQuery.ajax({
      url: "lib/jquery-bbq/jquery.ba-bbq.js",
      dataType: "script",
      async: false,
      cache: true
   });

   // The "tab widgets" to handle.
   var tabs = jQuery(".tabs").tabs({
      "show": function(event, ui) {
         var tab = jQuery(ui.panel);

         // Resize chart in tabs
         var chart = tab.find('.chart');
         if(chart.length != 0) {
            var plot = chart.data('jqplot');
            if (plot._drawCount === 0) {
               chart.height(tab.height()*0.96);
               chart.width(tab.width()*0.96);
               plot.replot();
            }
         }

         // Resize table in tabs
         var table = tab.find('div.dataTables_scrollBody>table.display');
         if(table.length != 0) {
            var oTable = table.dataTable();
            if (oTable.length > 0) {
               oTable.fnAdjustColumnSizing();
            }
         }
      }
   });

   // This selector will be reused when selecting actual tab widget A elements.
   var tab_a_selector = 'ul.ui-tabs-nav a';

   // Enable tabs on all tab widgets. The `event` property must be overridden so
   // that the tabs aren't changed on click, and any custom event name can be
   // specified. Note that if you define a callback for the 'select' event, it
   // will be executed for the selected tab whenever the hash changes.
   tabs.tabs({ event: 'change' });

   // Define our own click handler for the tabs, overriding the default.
   tabs.find(tab_a_selector).click(function(){
      var state = {};

      // Get the id of this tab widget.
      var id = jQuery(this).closest('.tabs').attr('id');

      // Get the index of this tab.
      var idx = jQuery(this).parent().prevAll().length;

      // Set the state!
      state[id] = idx;
      jQuery.bbq.pushState(state);
   });

   // Bind an event to window.onhashchange that, when the history state changes,
   // iterates over all tab widgets, changing the current tab as necessary.
   jQuery(window).bind('hashchange', function(e) {

      // Iterate over all tab widgets.
      tabs.each(function(){

         // Get the index for this tab widget from the hash, based on the
         // appropriate id property. In jQuery 1.4, you should use e.getState()
         // instead of $.bbq.getState(). The second, 'true' argument coerces the
         // string value to a number.
         var idx = jQuery.bbq.getState(this.id, true) || 0;

         // Select the appropriate tab for this tab widget by triggering the custom
         // event specified in the .tabs() init above (you could keep track of what
         // tab each widget is on using .data, and only select a tab if it has
         // changed).
         jQuery(this).find(tab_a_selector).eq(idx).triggerHandler('change');
      });
   });

   // Since the event is only triggered when the hash changes, we need to trigger
   // the event now, to handle the hash the page may have loaded with.
   jQuery(window).trigger('hashchange');

});