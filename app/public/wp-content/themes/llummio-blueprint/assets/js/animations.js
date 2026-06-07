// GSAP-powered animations for pages that opt in from Llummio Editor Helpers.
// Add reusable animation patterns here and trigger them with clear CSS classes.
document.addEventListener("DOMContentLoaded", () => {
    if (!window.gsap) {
        return;
    }

    document.documentElement.classList.add("llummio-gsap-ready");
    Animate();

    if (window.ScrollTrigger) {
        window.gsap.registerPlugin(window.ScrollTrigger);
        document.documentElement.classList.add("llummio-scrolltrigger-ready");
        AnimateOnTrigger();
    }
});


function Animate() {
    console.log("Animating page");

    // Create a timeline
    let tl = gsap.timeline()

    // add the tweens to the timeline - Note we're using tl.to not gsap.to
    tl.from(".box", { y: 40, autoAlpha: 0, duration: 1, stagger: 0.1 });
}


function AnimateOnTrigger() {
    console.log("Animating on scroll trigger");
    // Create a timeline
    let tl = gsap.timeline()

    // add the tweens to the timeline - Note we're using tl.to not gsap.to
    gsap.from(".el-trigger", {
        scrollTrigger: {
            trigger: ".animation-trigger-container",
            markers: true,
            toggleActions: "restart pause reverse pause"
        },
        y: 40,
        autoAlpha: 0,
        duration: 1,
        stagger: 0.2
    });
}