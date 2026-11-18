document.addEventListener("DOMContentLoaded", () => {
    let serviceWorkerSetUp = null;
    /**
     * The service worker is opt-in (plugin configuration). With a URL it is registered once
     * per page; without one, a registration left over from an earlier opt-in is removed.
     * Failures are logged but never block rendering.
     * @param {string|null} serviceWorkerUrl
     */
    const setUpServiceWorker = (serviceWorkerUrl) => {
        if (!('serviceWorker' in navigator)) {
            return Promise.resolve();
        }
        if (serviceWorkerSetUp === null) {
            serviceWorkerSetUp = (async () => {
                if (serviceWorkerUrl) {
                    await navigator.serviceWorker.register(serviceWorkerUrl, {
                        scope: '/'
                    });
                    await navigator.serviceWorker.ready;
                    return;
                }
                const registrations = await navigator.serviceWorker.getRegistrations();
                for (const registration of registrations) {
                    const worker = registration.active || registration.waiting || registration.installing;
                    if (worker && worker.scriptURL.includes('/LfEduSharingResource/serviceWorker.php')) {
                        await registration.unregister();
                    }
                }
            })().catch(error => console.error(error));
        }
        return serviceWorkerSetUp;
    };
    /**
     * @param {Element} element
     */
    const renderObject = async (element) => {

        await setUpServiceWorker(element.getAttribute("data-service-worker"));
        const resourceId = element.getAttribute("data-resourceid");
        const repoUrl = element.getAttribute("data-repo");
        const nodeId = element.getAttribute("data-nodeid");
        const version = element.getAttribute("data-version");
        const containerId = element.getAttribute("data-containerid");
        const nodeEndpoint = element.getAttribute("data-endpoint");
        const redirectUrl = element.getAttribute("data-redirecturl");
        const nodeUrl = `${nodeEndpoint}?nodeId=${encodeURIComponent(nodeId)}&resourceId=${encodeURIComponent(resourceId)}&version=${encodeURIComponent(version)}&containerId=${encodeURIComponent(containerId)}`;
        const securedNodeResponse = await fetch(
            nodeUrl,
            {
                method: "GET",
                headers: {
                    "Accept": "application/json"
                }
            }
        ).catch(
            error => console.error(error)
        );
        const node = await securedNodeResponse.json();
        const renderComponent = document.createElement('edu-sharing-render');
        renderComponent.encoded_node = node.data.securedNode;
        renderComponent.signature = node.data.signature;
        renderComponent.jwt = node.data.jwt;
        renderComponent.render_url = node.data.renderingBaseUrl;
        renderComponent.service_worker_url = "";
        renderComponent.activate_service_worker = false;
        renderComponent.assets_url = repoUrl + '/web-components/rendering-service/assets';
        renderComponent.resource_url = redirectUrl;
        renderComponent.preview_url = node.data.previewUrl;
        renderComponent.signature_algorithm = node.data.signingAlgorithm;
        element.innerHTML = "";
        element.removeAttribute('data-type');
        element.appendChild(renderComponent);
    }

    const observerCallback = async(entries, observer) => {
        for (const entry of entries) {
            await renderObject(entry.target);
            observer.unobserve(entry.target);
        }
    };
    const options = {
        root: null,
        rootMargin: "400px",
        threshold: 0
    };
    const observer = new IntersectionObserver(observerCallback, options);
    const allEduSharingObjects = document.querySelectorAll("div[data-type='esObject']");
    allEduSharingObjects.forEach(element => observer.observe(element));
});
