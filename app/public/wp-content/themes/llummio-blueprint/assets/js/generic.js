const consoleMessages = false;

document.addEventListener("DOMContentLoaded", () => {
    logosCarousel();
    animateShapeArrows();
});

// Function to initialize the logos carousel
const logosCarousel = () => {
    consoleMessages ? console.log("Logos carousel function called") : null;
    const carousel = document.querySelector('.logos-container');

    if (!carousel) {
        consoleMessages ? console.log("Logos carousel container not found") : null;
        return;
    } else {
        consoleMessages ? console.log("Logos carousel container found") : null;
        animateLogosCarousel();
    }
}

// Function to animate the logos carousel using GSAP
const animateLogosCarousel = () => {
    consoleMessages ? console.log("Animating logos carousel") : null;
    const carousel = document.getElementsByClassName('logos-container');

    let tl = gsap.timeline({ delay: 0, repeat: -1 });

    //sequenced one-after-the-other
    tl.to(carousel, { duration: 20, xPercent: -100, ease: 'none' })
        .to(carousel, { duration: 20, xPercent: -100, ease: 'none' }, "<") // start at the same time as the previous animation;
}

// Function to animate the arrows using GSAP
const animateShapeArrows = () => {
    consoleMessages ? console.log("Animating arrows") : null;
};
