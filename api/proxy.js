// Simple proxy for GitHub Pages
// This will be served as a static file

const PROXY_URL = 'https://mbmoodys.github.io/proxy.html';

// Function to make proxied requests
async function proxyRequest(url, options = {}) {
    const params = new URLSearchParams({
        url: url,
        method: options.method || 'GET',
        headers: JSON.stringify(options.headers || {}),
        body: options.body || ''
    });
    
    const response = await fetch(`${PROXY_URL}?${params}`);
    return response;
}

// Export for use in other files
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { proxyRequest };
} else {
    window.proxyRequest = proxyRequest;
}
