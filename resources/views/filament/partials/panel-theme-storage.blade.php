<script>
    (() => {
        const panelThemeKey = `lumina:theme:{{ $panelId }}`
        const storage = window.Storage.prototype
        const originalGetItem = storage.getItem
        const originalSetItem = storage.setItem
        const originalRemoveItem = storage.removeItem
        const resolveKey = (key) => key === 'theme' ? panelThemeKey : key

        storage.getItem = function (key) {
            return originalGetItem.call(this, resolveKey(key))
        }

        storage.setItem = function (key, value) {
            return originalSetItem.call(this, resolveKey(key), value)
        }

        storage.removeItem = function (key) {
            return originalRemoveItem.call(this, resolveKey(key))
        }
    })()
</script>
