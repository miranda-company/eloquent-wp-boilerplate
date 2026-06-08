// GSAP-powered animations for pages that opt in from Llummio Editor Helpers.
// Add reusable animation patterns here and trigger them with clear CSS classes.
document.addEventListener("DOMContentLoaded", () => {
    if (!window.gsap) {
        return;
    }

    document.documentElement.classList.add("llummio-gsap-ready");

    animations();

    if (window.ScrollTrigger) {
        window.gsap.registerPlugin(window.ScrollTrigger);
        document.documentElement.classList.add("llummio-scrolltrigger-ready");

        animateOnTrigger();
    }

    if (window.SplitText) {
        window.gsap.registerPlugin(window.SplitText);
        document.documentElement.classList.add("llummio-splittext-ready");

        animateSplitText();
    }
});


function animations() {
    const tl = gsap.timeline();
    tl.from(".anim-el", { y: 40, autoAlpha: 0, duration: 1, stagger: 0.25 });
}


function animateOnTrigger() {
    gsap.from(".anim-el-on-trigger", {
        scrollTrigger: {
            trigger: ".animation-trigger-container",
            markers: true,
            toggleActions: "restart pause reverse pause"
        },
        y: 40,
        autoAlpha: 0,
        duration: 1,
        stagger: 0.25
    });
}

function animateSplitText() {
    console.log("Split Text Animation");

    var split = SplitText.create(".split-text", {
        type: "words"
    });

    gsap.from(split.words, {
        y: 40,
        autoAlpha: 0,
        stagger: 0.25
    });

}
