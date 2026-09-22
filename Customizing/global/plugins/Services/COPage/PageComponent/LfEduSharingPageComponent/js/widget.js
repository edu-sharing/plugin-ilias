document.addEventListener("DOMContentLoaded", () => {
    /**
     * @param {Element} widgetContainer
     */
    const renderWidget = (widgetContainer) => {
        const json = widgetContainer.getAttribute("data-widget");
        const data = JSON.parse(json);
        const widget = document.createElement("edu-sharing-generic-widget");
        Object.entries(data).forEach(([name, value]) => {
            if (value === null || value === undefined || value === false) {
                return;
            }
            if (value === true) {
                widget.setAttribute(name, '');
                return;
            }
            widget.setAttribute(name, String(value));
        });
        widgetContainer.classList.remove("edu-widget");
        widgetContainer.appendChild(widget);

    }
    const observerCallback = async(entries, observer) => {
        for (const entry of entries) {
            renderWidget(entry.target);
            observer.unobserve(entry.target);
        }
    };
    const options = {
        root: null,
        rootMargin: "400px",
        threshold: 0
    };
    const observer = new IntersectionObserver(observerCallback, options);
    const widgetContainers = document.querySelectorAll(".edu-widget");
    widgetContainers.forEach(element => observer.observe(element));
});
