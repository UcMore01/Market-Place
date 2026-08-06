(function() {
    'use strict';
    const STORAGE_KEY = 'guest_wishlist';
    const EVENT_NAME = 'guest_wishlist:changed';

    function load() {
        try {
            return JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
        } catch (e) {
            return [];
        }
    }

    function save(items) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(items));
        window.dispatchEvent(new CustomEvent(EVENT_NAME, { detail: { count: items.length } }));
    }

    return window.guestWishlist = {
        getAll: function() { return load(); },
        count: function() { return load().length; },
        isInWishlist: function(productId) { return load().includes(Number(productId)); },
        add: function(productId) {
            const items = load();
            const id = Number(productId);
            if (!items.includes(id)) {
                items.push(id);
                save(items);
            }
        },
        remove: function(productId) {
            const items = load().filter(id => id !== Number(productId));
            save(items);
        },
        toggle: function(productId) {
            const id = Number(productId);
            if (this.isInWishlist(id)) {
                this.remove(id);
                return false;
            } else {
                this.add(id);
                return true;
            }
        },
        clear: function() {
            localStorage.removeItem(STORAGE_KEY);
            window.dispatchEvent(new CustomEvent(EVENT_NAME, { detail: { count: 0 } }));
        },
        onChange: function(callback) {
            window.addEventListener(EVENT_NAME, function(e) {
                callback(e.detail.count);
            });
        }
    };
})();
