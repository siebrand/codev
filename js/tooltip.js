/*
 * Tooltip
 * Convention :
 * - <xxx class="haveTooltip">...</xxx>
 * - <div class="tooltip ui-helper-hidden">...</div>
 */
jQuery(document).ready(function() {
   var haveTooltip = jQuery(".haveTooltip");
   if(haveTooltip.length > 0) {
      // Lib handle the tooltip
      jQuery.ajax({
         url: "lib/jquery.powertip/jquery.powertip.min.js",
         dataType: "script",
         async: false,
         cache: true
      });

      applyTooltip("body");
   }
});

function applyTooltip(context) {
   jQuery(context).find(".haveTooltip").each(function() {
      var container = jQuery(this);
      var tooltip = container.next('.tooltip');
      if(tooltip.length > 0) {
         tooltip.remove();
         tooltip.removeClass('ui-helper-hidden');
         container.data('powertipjq', jQuery(tooltip.html()));

         container.powerTip({
            placement: 'n',
            smartPlacement: true,
            mouseOnToPopup: true
         });
      }
   });
}
