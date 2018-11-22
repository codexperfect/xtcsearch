(function ($, Drupal, drupalSettings) {
  var masonryIsEnabled = drupalSettings.xtcsearch.pager.masonry;

  Drupal.behaviors.xtc_filter = {
    attach: function (context, settings) {
      // $("#filter-div").trigger("change");
      //Uniquement page ou y a Masonry
      var $grid = $('.gallery-wrapper').masonry({
        itemSelector: '.grid-item',
        columnWidth: '.grid-sizer',
        horizontalOrder: true,
        percentPosition: true,
        transitionDuration: 500,
      });
      setTimeout(function () {
        $grid.masonry('reloadItems');
        $grid.masonry('layout');
      }, 251);
    }
  }

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

  showFilterOnPage();

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

    Drupal.behaviors.xtc_filter.attach();
  }

  /*
  ###################################################
  #        Checkbox Control by Mr Gilles            #
  ###################################################
  */
  var checked, parent, child;

  $('input:checkbox').click(function () {
    checked = $(this).is(':checked');
    parent = $(this).data('parent');
    child = $(this).data('child');
    checkOrUncheckInput(checked, parent, child);
  });

  function checkOrUncheckInput(checked, parent, child) {
    //cas du cochage du checkbox
    if (checked) {
      //Lorsqu'on a coché un checkox parent , on cose tous ses fils
      checkInput(parent, child);
    }
    else { //cas du décochage du checkbox
      //Lorsqu'on a décoché un checkox parent , on décoche tous ses fils
      uncheckedInput(parent, child);
    }
  }

  function checkInput(parent, child) {
    //Lorsqu'on a coché un checkox parent , on cose tous ses fils
    if (typeof parent !== typeof undefined && parent !== false) {
      var dataChild = "[data-child=" + parent + "]";
      var dataParent = "[data-parent=" + parent + "]";
      $(dataParent).prop("checked", true);
      $(dataChild).prop("checked", true);
      if ($(dataChild).length > 0) {
        $(dataParent).attr('type', 'hidden');
      }
    }
    else {
      if (typeof child !== typeof undefined && child !== false) {
        var dataParent = "[data-parent=" + child + "]";
        var dataChild = "[data-child=" + child + "]";
        $(dataParent).prop("checked", false);
        if ($(dataChild).length == $(dataChild + ':checked').length) {
          $(dataParent).prop("checked", true);
          $(dataParent).attr('type', 'hidden');
        }

      }
    }
    if ($(dataChild).length > 0) {
      $(dataParent).attr("disabled", "disabled");
    }
  }

  function uncheckedInput(parent, child) {
    if (typeof parent !== typeof undefined && parent !== false) {
      var dataChild = "[data-child=" + parent + "]";
      var dataParent = "[data-parent=" + parent + "]";
      $(dataParent).prop("checked", false);
      $(dataChild).prop("checked", false);
    }
    else {
      if (typeof child !== typeof undefined && child !== false) {
        var dataParent = "[data-parent=" + child + "]";
        $(dataParent).prop("checked", false);
        $(dataParent).removeAttr("disabled");
        $(dataParent).attr('type', 'checkbox');
      }
    }

  }

  $("[data-parent]").each(function (i, o) {
    var parent = $(this).attr('data-parent');
    var child = false;
    var checked = $(this).is(':checked');
    if (checked) {
      //Lorsqu'on a coché un checkbox parent , on cose tous ses fils
      checkInput(parent, child);
    }
  });

  $("[data-child]").each(function (i, o) {
    var child = $(this).attr('data-child');
    var parent = false;
    var checked = $(this).is(':checked');
    if (checked) {
      //Lorsqu'on a coché un checkbox enfant , on cose son parent
      checkInput(parent, child);
    }
  });
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
