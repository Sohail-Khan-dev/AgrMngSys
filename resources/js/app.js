import './bootstrap';
import AOS from "aos";
import "aos/dist/aos.css";

AOS.init();
import GLightbox from "glightbox";
import "glightbox/dist/css/glightbox.min.css";

const lightbox = GLightbox();

import Swiper from "swiper";
import "swiper/css";

const swiper = new Swiper(".swiper-container", {
    // Add your Swiper configuration here
});

