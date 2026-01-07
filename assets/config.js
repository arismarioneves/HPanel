/**
 * Hostinger Dashboard Configuration Manager
 * Stores session hash in localStorage, token is stored server-side
 */

const HostingerConfig = {
    STORAGE_KEY: 'hostinger_session_hash',

    /**
     * Get current session hash from localStorage
     * @returns {string|null} Session hash
     */
    getHash() {
        return localStorage.getItem(this.STORAGE_KEY);
    },

    /**
     * Save session hash to localStorage and cookie
     * @param {string} hash - Session hash
     */
    setHash(hash) {
        localStorage.setItem(this.STORAGE_KEY, hash);
        // Also save to cookie for PHP direct page access
        document.cookie = `hostinger_session=${hash}; path=/; max-age=${60 * 60 * 24 * 30}`; // 30 days
    },

    /**
     * Check if session is configured
     * @returns {boolean} True if hash exists
     */
    isConfigured() {
        const hash = this.getHash();
        return hash && hash.length > 0;
    },

    /**
     * Clear session hash from localStorage and cookie
     */
    clear() {
        localStorage.removeItem(this.STORAGE_KEY);
        // Also clear cookie
        document.cookie = 'hostinger_session=; path=/; max-age=0';
    },

    /**
     * Get headers object for fetch requests
     * @returns {Object} Headers with session hash
     */
    getHeaders() {
        return {
            'X-Session-Hash': this.getHash() || '',
            'Content-Type': 'application/json'
        };
    },

    /**
     * Make authenticated fetch request
     * @param {string} url - URL to fetch
     * @param {Object} options - Additional fetch options
     * @returns {Promise<Response>}
     */
    async fetch(url, options = {}) {
        const headers = {
            ...this.getHeaders(),
            ...(options.headers || {})
        };

        return fetch(url, {
            ...options,
            headers
        });
    },

    /**
     * Save JWT token to server
     * @param {string} token - JWT token
     * @param {string} gaid - Google Analytics ID (optional)
     * @returns {Promise<Object>} Result with success and hash
     */
    async save(token, gaid = 'GA1.1.000000000.0000000000') {
        const existingHash = this.getHash();

        const response = await fetch('api/save-config.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                token: token,
                gaid: gaid,
                hash: existingHash
            })
        });

        const data = await response.json();

        if (data.success && data.hash) {
            this.setHash(data.hash);
        }

        return data;
    },

    /**
     * Get JWT status (expiration time, etc)
     * @returns {Promise<Object>} JWT status info
     */
    async getJwtStatus() {
        if (!this.isConfigured()) return { success: false };

        try {
            const response = await this.fetch('api/jwt-status.php');
            return await response.json();
        } catch (error) {
            console.error('Error getting JWT status:', error);
            return { success: false, error: error.message };
        }
    },

    /**
     * Initialize - sync localStorage hash to cookie if needed
     * This ensures PHP can read the session on direct page navigation
     */
    init() {
        const hash = this.getHash();
        if (hash) {
            // Ensure cookie is set (in case localStorage was set before cookie code existed)
            const cookieHash = this.getCookie('hostinger_session');
            if (cookieHash !== hash) {
                document.cookie = `hostinger_session=${hash}; path=/; max-age=${60 * 60 * 24 * 30}`;
            }
        }
    },

    /**
     * Get a cookie value by name
     * @param {string} name - Cookie name
     * @returns {string|null}
     */
    getCookie(name) {
        const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        return match ? match[2] : null;
    }
};

// Auto-initialize when script loads
HostingerConfig.init();

// Export for module systems if available
if (typeof module !== 'undefined' && module.exports) {
    module.exports = HostingerConfig;
}
