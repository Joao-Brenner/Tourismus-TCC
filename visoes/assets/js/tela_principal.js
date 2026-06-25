const hamburger = document.getElementById("hamburger");
const menu = document.getElementById("menu");
const overlay = document.getElementById("overlay");

hamburger.addEventListener("click", (event) => {
  event.stopPropagation();
  menu.classList.toggle("show");
  overlay.classList.toggle("show");
});

overlay.addEventListener("click", () => {
  menu.classList.remove("show");
  overlay.classList.remove("show");
});

  const msg = document.getElementById("flash-msg");
if (msg) {
    msg.style.opacity = "1";
    setTimeout(() => {
        msg.style.opacity = "0";
        setTimeout(() => msg.remove(), 500);
    }, 2500);
}
