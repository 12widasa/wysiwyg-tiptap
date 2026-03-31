import "./bootstrap";

import Alpine from "alpinejs";
window.Alpine = Alpine;

import "./wysiwyg";

document.addEventListener("DOMContentLoaded", () => {
    Alpine.start();
});