document.addEventListener("DOMContentLoaded", function () {
  var btn = document.getElementById("menuBtn"),
    sb = document.getElementById("sidebar");
  if (btn && sb)
    btn.addEventListener("click", function () {
      sb.classList.toggle("open");
    });
  document.querySelectorAll("form[data-confirm]").forEach(function (f) {
    f.addEventListener("submit", function (e) {
      if (!confirm(f.getAttribute("data-confirm"))) e.preventDefault();
    });
  });
  setTimeout(function () {
    document.querySelectorAll(".flash").forEach(function (f) {
      f.style.transition = "opacity .6s";
      f.style.opacity = 0;
    });
  }, 5000);
});
