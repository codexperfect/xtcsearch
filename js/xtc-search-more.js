(function ($, Drupal) {

  Drupal.behaviors.xtc_pager_more = {
    attach: function (context, settings) {
      $("#news-list-div").trigger("change");

      var sizerClass = "px-0 px-md-3 col-sm-12";
      if ($("#filter-button").hasClass("filter-button-active")) {
        sizerClass += " col-md-12 col-lg-6";
      }
      else
      {
        sizerClass += " col-md-6 col-lg-4";
      }

      var sizer = $("#container-news-filter").find("div.grid-sizer");
      $.each(sizer, function (index, value) {
        value.className = "grid-sizer " + sizerClass;
      });

      var items = $("#container-news-filter").find("div.grid-item");
      $.each(items, function (index, value) {
        value.className = "grid-item " + sizerClass;
      });

      Drupal.behaviors.xtc_masonry.attach();
    }
  }

})(jQuery, Drupal);
