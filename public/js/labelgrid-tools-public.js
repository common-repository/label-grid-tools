jQuery(document).ready(function ($) {
  "use strict";
  const { __, _x, _n, sprintf } = wp.i18n;

  var loadedga = null;
  var loadedfbq = null;
  var loadedgtag = null;

  if (typeof ga === "function") loadedga = true;
  if (typeof gtag === "function") loadedgtag = true;
  if (typeof fbq === "function") loadedfbq = true;

  // Fetch country code using geoplugin.net
  fetch("/wp-json/lgt-api/v1/geolocation", {
    method: "GET",
    headers: {
      "X-WP-Nonce": LabelGridTools.nonce,
    },
  })
    .then((response) => response.json())
    .then((data) => {
      var countryCode = data.geoplugin_countryCode;

      if (typeof countryCode !== "undefined") {
        var langCode = countryCode.toLowerCase();

        function render(data) {
          if (data) {
            var item;
            if (data.artistLinkUrl != null) item = data.artistLinkUrl;
            else item = data.collectionViewUrl;
            if (typeof itunes_affiliate_token !== "undefined")
              item =
                item + "&at=" + itunes_affiliate_token + "&ct=labelgridtools";
            if ($("#releaselinks .linkTop.itunes A").attr("href")) {
              $("#releaselinks .linkTop.itunes A").attr(
                "href",
                item + "&app=itunes"
              );
            }
            if ($("#releaselinks .linkTop.applemusic A").attr("href")) {
              $("#releaselinks .linkTop.applemusic A").attr(
                "href",
                item + "&app=music"
              );
            }
          }
        }

        if ($("#releaselinks .linkTop.itunes A").attr("href")) {
          var urname = $("#releaselinks .linkTop.itunes A")
            .attr("href")
            .split("/");
          var urname_id = urname.pop().split("?");
          var iTunesId = urname_id[0].replace("id", "");
          iTunesLiveTile.get(iTunesId, langCode, render);
        }

        if ($("#releaselinks .linkTop.applemusic A").attr("href")) {
          var urname = $("#releaselinks .linkTop.applemusic A")
            .attr("href")
            .split("/");
          var urname_id = urname.pop().split("?");
          var iTunesId = urname_id[0].replace("id", "");
          iTunesLiveTile.get(iTunesId, langCode, render);
        }
      }
    })
    .catch((error) => console.error("Error fetching IP info:", error));

  // Analytics for links
  $("#releaselinks").on("click", ".linkTop A", function (e) {
    if (loadedga && typeof $(this).data("service") != "undefined")
      ga(getGa("send"), {
        hitType: "event",
        eventCategory: "link-service",
        eventAction: $(this).attr("href"),
        eventLabel: $(this).data("service"),
      });
    if (loadedgtag && typeof $(this).data("service") != "undefined")
      gtag("event", "link-service", {
        action: $(this).attr("href"),
        service: $(this).data("service"),
      });
    if (loadedfbq && typeof $(this).data("service") != "undefined")
      fbq("trackCustom", "ServiceLinkClick", {
        service: $(this).data("service"),
        toUrl: $(this).attr("href"),
        release: $(location).attr("href"),
      });
  });

  // Release list search filter
  $("#lg_release_filter_genre").on("change", function () {
    document.getElementById("lg_release_list_filter").submit();
  });

  $("#lg_release_filter_record_label").on("change", function () {
    document.getElementById("lg_release_list_filter").submit();
  });
});

function getGa(name) {
  if (typeof ga.getAll === "function") {
    var trackers = ga.getAll();
    var firstTracker = trackers[0];
    if (typeof firstTracker !== "undefined") {
      var trackerName = firstTracker.get("name");
      return trackerName + "." + name;
    } else {
      return name;
    }
  } else {
    return name;
  }
}

var iTunesLiveTile = {
  _callback: function () {},
  _fullUrl: function (id, langCode) {
    var SEARCH_URL_PRE = "https://itunes.apple.com/lookup?id=";
    var SEARCH_URL_POST =
      "&country=" + langCode + "&callback=iTunesLiveTile._prepareData";
    return SEARCH_URL_PRE + id + SEARCH_URL_POST;
  },
  get: function (id, langCode, callback) {
    _callback = callback;
    jQuery.getScript(this._fullUrl(id, langCode));
  },
  _prepareData: function (data) {
    var item = data.results[0];
    _callback(item);
  },
};
