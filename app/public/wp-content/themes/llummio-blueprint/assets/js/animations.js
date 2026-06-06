// GSAP-powered animations for pages that opt in from Llummio Editor Helpers.
// Add reusable animation patterns here and trigger them with clear CSS classes.
document.addEventListener("DOMContentLoaded", () => {
    if (!window.gsap) {
        return;
    }

    document.documentElement.classList.add("llummio-gsap-ready");
});
