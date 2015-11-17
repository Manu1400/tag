//@see http://ericleads.com/2013/10/javascript-string-contains/
if (!('contains' in String.prototype)) { //exist on ECMAScript 2015 (ES6)
   String.prototype.contains = function(str, startIndex) {
      return ''.indexOf.call(this, str, startIndex) !== -1;
   };
}

// Return string
function getTagsSearch() {
   var result = [];
   //Parse url query
   decodeURIComponent(location.search).split('&').forEach(function (item) {
      tmp = item.split("="); //console.log(tmp);
      if (tmp[0].contains('_plugin_tag_tag_values[]')) {
         result.push(decodeURIComponent(tmp[1]));
      }
   });

   return result.join(",");
}

function idealTextColor(hexTripletColor) {
   var nThreshold = 105;
   hexTripletColor.replace(/^#/,'')
   var components = {
      R: parseInt(hexTripletColor.substring(0, 2), 16),
      G: parseInt(hexTripletColor.substring(2, 4), 16),
      B: parseInt(hexTripletColor.substring(4, 6), 16)
   };
   var bgDelta = (components.R * 0.299) + (components.G * 0.587) + (components.B * 0.114);
   return ((255 - bgDelta) < nThreshold) ? "#000000" : "#ffffff";   
}

function formatOption(option) {
   var color = option.element[0].getAttribute("data-color-option");
   var template = "<span style='padding: 2px; border-radius: 3px; ";
   if (color !== "") {
      var invertedcolor = idealTextColor(color);

      template+= " background-color: " + color + "; ";
      template+= " color: " + invertedcolor + "; ";
   }
   template+= "'>" + option.text + "</span>";

   return template;
}

function showTagsOnKB(selector) {
   var itemtype = 'knowbaseitem';

   // Tags search by user
   var plugin_tag_tag_values = getTagsSearch();

   var data = "itemtype=" + itemtype;

   if (plugin_tag_tag_values != '') {
      data += "&_plugin_tag_tag_values=" + plugin_tag_tag_values;
   }
   
   var hidden_fields = "<input type='hidden' name='plugin_tag_tag_itemtype' value='"+itemtype+"'>";
   $.ajax({
      type: "POST",
      url: "../plugins/tag/ajax/tags_values_on_kb.php",
      data: data, //+ "&id=" + id,
      success: function(msg){
         if ($("#mainformtable").find("[name='plugin_tag_tag_itemtype']").length == 0) {
            $("form table tr").before(msg + hidden_fields);
            $("form .chosen-select-no-results").select2({
                'formatResult': formatOption,
                'formatSelection': formatOption
            });
         }
      }
   });
}

//Quick fix : search event on all webpage
$("html").on("tabsload", function( event, ui ) {
   console.log(ui.panel.selector);

   if (ui.panel.selector == "#ui-tabs-1") {
      showTagsOnKB(1);
   }
   if (ui.panel.selector == "#ui-tabs-2") {
      showTagsOnKB(2);
   }
});

$(document).ready(function() {

   //TODO : Add on 'Parcourir' tab (second tab)

   //Add tag to form only on the first tab (tab 'Search')
   /*
   $(".ui-tabs-panel:visible:contains('Knowbase$1')").ready(function() { //find("input[type='submit']:visible"
      console.log("Knowbase$1");
      showTagsOnKB();
   });

   $(".ui-tabs-panel:visible:contains('Knowbase$2')").ready(function() { //find("input[type='submit']:visible"
      console.log("Knowbase$2");
      showTagsOnKB();
   });
   */
   /*
   $("div[aria-expanded='true']").ready(function() {
      console.log('expanded');
      showTagsOnKB();
   });

   $("#tabspanel + div.ui-tabs").on("tabsload", function( event, ui ) {
      console.log(ui.panel.selector);
      if (ui.panel.selector == "#ui-tabs-2") {
         showTagsOnKB(2);
      }
      if (ui.panel.selector == "#ui-tabs-1") {
         showTagsOnKB(1);
      }
   });
*/

   $(".ui-tabs-panel:visible").find(".headerRow:visible").ready(function() {
      //showTagsOnKB();
   });


});
