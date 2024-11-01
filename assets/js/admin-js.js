jQuery(document).ready(function ($) {
  $("#wsmssr_icon_color_html").wpColorPicker();
  $("#wsmssr_icon_hover_color_html").wpColorPicker();

  $(document).on("click", ".wsmssr_delete_rating_btn", function (event) {
    event.preventDefault();

    if (confirm("Are you sure to delete all ratings?")) {
      $.ajax({
        url: ajax_var.url,
        type: "post",
        data: {
          action: "wsmssr_admin_delete_feeds",
          nonce: ajax_var.nonce, // pass the nonce here
        },
        success(data) {
          show_copied_toast("All ratings are deleted.");
        },
      });
    }
  });
});

copyshortcode = function (onfunction) {
  var get_data_short = jQuery(onfunction)
    .parent()
    .find("h4 code")
    .attr("data-short");
  copyToClipboard(get_data_short);
  show_copied_toast("Copied");
  var x = document.getElementById("notification_display");
  return false;
};

function show_copied_toast(msg) {
  var x = document.getElementById("notification_display");
  x.innerHTML += msg;
  x.className = "show";
  setTimeout(function () {
    x.className = x.className.replace("show", "");
  }, 1800);
  setTimeout(function () {
    if (x.innerHTML) {
      x.innerHTML = "";
    }
  }, 2000);
}

function copyToClipboard(text) {
  const elem = document.createElement("textarea");
  elem.value = text;
  document.body.appendChild(elem);
  elem.select();
  document.execCommand("copy");
  document.body.removeChild(elem);
}
