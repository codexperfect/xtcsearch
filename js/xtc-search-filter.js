(function ($, Drupal, drupalSettings) {
  var masonryIsEnabled = drupalSettings.xtcsearch.pager.masonry;

  /*
   * Filter button
   */
  $("#filter-button").click(function (e) {
    e.preventDefault();
    showHideFilters();
  });
  $("#filter-button-sm").click(function (e) {
    e.preventDefault();
    showHideFilters();
  });

  // Afficher/Cacher les filtres a facettes avec changement des class col-*
  function showHideFilters() {
    // SI LE FILTRE ETAIT OUVERT JE LE REFERME AU CLIQUE
    if ($("#filter-button").hasClass("filter-button-active")) {
      closeFilter();
    }
    else // SINON JE L'OUVRE AU CLIQUE
    {
      openFilter();
    }
  }

  $("#filter-div").ready(function () {
    showFilterOnPage();
  });

  $(window).resize(function () {
    showFilterOnPage();
  });

  /* Cette fonction permet d'avoir un affichage par défaut les filtres lorsqu'on arrive sur la page
  *  pour des écrans de taille minimale tablette
  *
  */
  function showFilterOnPage() {
    if ($(window).width() >= 768) {
      openFilter();
    } else {
      closeFilter();
    }
  }

  function closeFilter() {
    $("#filter-button").removeClass("filter-button-active");
    $("#filter-button").html("Afficher les filtres");
    filterPosition();

    $("#filter-div").hide();

    $("#news-elements").removeClass("col-12 col-md-8");
    $("#news-elements").addClass("col-12");

    var sizerClass = "px-0 px-md-3 col-sm-12";
    if (masonryIsEnabled) {
      sizerClass += " col-md-6 col-lg-4";
      refreshMasonry(sizerClass);
    }
    else {
      sizerClass += " col-md-12 col-lg-6";
    }

  }

  function openFilter() {
    $("#filter-button").addClass("filter-button-active");
    $("#filter-button").html("Cacher les filtres");

    $("#news-elements").removeClass("col-12");
    $("#news-elements").addClass("col-12 col-md-8");

    setTimeout(function () {
      $("#filter-div").show();
    }, 250);
    setTimeout(function () {
      filterPosition();
    }, 251);

    var sizerClass = "px-0 px-md-3 col-sm-12";
    sizerClass += " col-md-12 col-lg-6";
    if (masonryIsEnabled) {
      refreshMasonry(sizerClass);
    }
  }

  function refreshMasonry(sizerClass) {
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
})(jQuery, Drupal, drupalSettings);

jQuery(window).on('load', function () {
  //Uniquement page avec filtre
  filterPosition();
});

jQuery(window).resize(function () {
  //Uniquement page avec filtre
  filterPosition();
});

//Uniquement page avec filtre
function filterPosition() {
  if (jQuery(window).outerWidth() < 768) {
    jQuery("#filter-div").addClass("position-overall");

    if (jQuery("#filter-button").hasClass("filter-button-active")) {
      jQuery("body").addClass("body-overflow");
    }
    else {
      jQuery("body").removeClass("body-overflow");
    }
  }
  else {
    jQuery("#filter-div").removeClass("position-overall");
    jQuery("body").removeClass("body-overflow");
  }
}
