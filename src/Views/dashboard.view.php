<?php
$pageTitle = 'SRP Traffic Control';
require __DIR__ . '/components/header.php';
?>
<div x-data="dash" x-cloak>
<header class="sticky top-0 z-50 w-full border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
    <div class="flex h-12 max-w-4xl mx-auto items-center px-5">
        <div class="mr-3 hidden md:flex">
            <a href="/" class="mr-4 flex items-center space-x-2">
                <img src="/assets/icons/fox-head.svg" alt="Fox head logo" class="h-5 w-5" width="20" height="20">
                <div class="flex flex-col leading-tight">
                <span class="font-semibold text-sm tracking-tight">SRP Smart Redirect Platform</span>
                 <span class="text-[11px] text-muted-foreground">No "smart" buzzword without actual routing logic.</span>
                 </div>
            </a>
        </div>

        <button @click="mobileMenuOpen = !mobileMenuOpen" class="mr-2 md:hidden btn btn-ghost btn-icon" aria-label="Toggle navigation">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>

        <div class="flex md:hidden items-center space-x-2">
            <img src="/assets/icons/fox-head.svg" alt="Fox head logo" class="h-4 w-4" width="16" height="16">
            <span class="font-semibold text-xs tracking-tight">SRP</span>
        </div>

        <div class="flex flex-1 items-center justify-end space-x-2">
            <div class="flex items-center space-x-2 rounded-md px-2 sm:px-2.5 py-1 transition-colors duration-200"
                 :class="cfg.system_on ? (muteStatus.isMuted ? 'bg-amber-500 text-white shadow-sm' : 'bg-primary text-primary-foreground shadow-sm') : 'border'">
                <div class="h-1.5 w-1.5 rounded-full transition-all duration-200"
                     :class="cfg.system_on ? (muteStatus.isMuted ? 'bg-white animate-pulse' : 'bg-emerald-500 animate-pulse') : 'bg-gray-400'"></div>
                <span class="text-[11px] font-medium hidden sm:inline"
                      x-text="cfg.system_on ? (muteStatus.isMuted ? 'Muted' : 'Active') : 'Offline'"></span>
            </div>

            <form method="post" action="/logout.php" class="hidden sm:block">
                <input type="hidden" name="csrf_token"
                       value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" class="btn btn-secondary btn-sm">Logout</button>
            </form>

            <form method="post" action="/logout.php" class="sm:hidden">
                <input type="hidden" name="csrf_token"
                       value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" class="btn btn-ghost btn-icon" aria-label="Logout">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v1" />
                    </svg>
                </button>
            </form>
        </div>
    </div>
</header>

<!-- Toast & Confirm Modal -->
<?php require __DIR__ . '/components/toast.php'; ?>

<main class="flex-1 w-full">
    <?php require __DIR__ . '/components/dashboard-content.php'; ?>
</main>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('dash', () => ({
        // Navigation
        activeTab: 'overview',
        mobileMenuOpen: false,

        // Configuration
        cfg: {
            system_on: false,
            redirect_url: '',
            country_filter_mode: 'all',
            country_filter_list: '',
            updated_at: 0
        },

        // Environment Config
        envConfig: {
            SRP_DB_HOST: 'localhost',
            SRP_DB_NAME: '',
            SRP_DB_USER: '',
            SRP_DB_PASS: '',
            SRP_API_URL: 'https://trackng.us/decision.php',
            SRP_API_KEY: '',
            IMONETIZEIT_CLIENT_ID: '13290',
            IMONETIZEIT_API_KEY: 'ff8cc8dc0da16083b86c0450d359b9458157778b53b18aac5ecdbc8077022f07',
            APP_ENV: 'production',
            APP_DEBUG: 'false',
            SESSION_LIFETIME: '3600',
            RATE_LIMIT_ATTEMPTS: '5',
            RATE_LIMIT_WINDOW: '900'
        },
        showApiKey: false,
        showImonetizeitKey: false,
        isSavingEnv: false,
        isTestingDb: false,
        isTestingSrp: false,

        // Logs
        logs: [],

        // Flash messages
        flash: '',
        flashType: 'info',

        // Decision Tester
        testerOpen: false,
        testInput: {
            country: '',
            device: 'mobile',
            vpn: 'no'
        },
        testResult: null,

        // Loading states
        isSavingCfg: false,
        savingCfgCount: 0,
        isClearingLogs: false,
        flashAction: '',

        // Mute status
        muteStatus: {
            isMuted: false,
            timeRemaining: '',
            cyclePosition: 0
        },

        // API Key Fetcher
        apiKeyFetcher: {
            endpoint: 'offers',
            startDate: new Date().toISOString().split('T')[0],
            endDate: new Date().toISOString().split('T')[0],
            limit: 50,
            isLoading: false,
            error: '',
            success: false,
            response: null
        },

        init() {
            this.refresh();
            this.loadEnvConfig();
            this.updateMuteStatus();
            setInterval(() => this.refresh(), 3000);
            setInterval(() => this.updateMuteStatus(), 1000);
        },

        csrf() {
            const el = document.querySelector('meta[name="csrf-token"]');
            return el && el.content ? el.content : '';
        },

        setFlash(message, type = 'info') {
            this.flashType = type;
            this.flash = message;

            if (!message || type === 'confirm') {
                return;
            }

            setTimeout(() => {
                if (this.flash === message && this.flashType === type) {
                    this.flash = '';
                }
            }, 4000);
        },

        async refresh() {
            try {
                const r = await fetch('data.php', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await r.json();

                if (data && data.ok) {
                    data.cfg.system_on = Boolean(Number(data.cfg.system_on));
                    this.cfg = data.cfg;
                    this.logs = Array.isArray(data.logs) ? data.logs : [];
                }
            } catch (e) {
                if (!this.flash) {
                    this.setFlash('Failed to refresh dashboard data', 'error');
                }
            }
        },

        async save() {
            if (this.cfg.redirect_url && !this.cfg.redirect_url.startsWith('https://')) {
                this.setFlash('Redirect URL must start with https://', 'error');
                return;
            }

            this.savingCfgCount += 1;
            this.isSavingCfg = true;

            try {
                const r = await fetch('data.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-Token': this.csrf()
                    },
                    body: JSON.stringify({
                        system_on: this.cfg.system_on,
                        redirect_url: this.cfg.redirect_url,
                        country_filter_mode: this.cfg.country_filter_mode,
                        country_filter_list: this.cfg.country_filter_list
                    })
                });

                if (!r.ok) {
                    this.setFlash('Failed to save configuration (HTTP ' + r.status + ')', 'error');
                    return;
                }

                this.setFlash('Configuration saved', 'info');
            } catch (e) {
                this.setFlash('Failed to save configuration', 'error');
            } finally {
                this.savingCfgCount -= 1;
                if (this.savingCfgCount <= 0) {
                    this.savingCfgCount = 0;
                    this.isSavingCfg = false;
                }
            }
        },

        clearLogs() {
            if (this.isClearingLogs) {
                return;
            }

            this.flashAction = 'clearLogs';
            this.setFlash('Clear all traffic logs? This action cannot be undone.', 'confirm');
        },

        cancelFlashAction() {
            this.flash = '';
            this.flashType = 'info';
            this.flashAction = '';
        },

        async confirmFlashAction() {
            if (this.flashType !== 'confirm') {
                return;
            }

            if (this.flashAction === 'clearLogs') {
                await this.performClearLogs();
            }
        },

        async performClearLogs() {
            if (this.isClearingLogs) {
                return;
            }

            this.isClearingLogs = true;

            try {
                const r = await fetch('data.php', {
                    method: 'DELETE',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-Token': this.csrf()
                    }
                });

                const result = await r.json();

                if (result && result.ok) {
                    this.logs = [];
                    const deleted = typeof result.deleted === 'number' ? result.deleted : 0;
                    this.setFlash('Successfully deleted ' + deleted + ' log entries', 'info');
                } else {
                    this.setFlash('Failed to clear logs', 'error');
                }
            } catch (e) {
                this.setFlash('Failed to clear logs', 'error');
            } finally {
                this.isClearingLogs = false;
                this.flashAction = '';
            }
        },

        isCountryAllowed(countryCode) {
            const code = (countryCode || '').toUpperCase().trim();
            const mode = this.cfg.country_filter_mode || 'all';

            if (mode === 'all') {
                return true;
            }

            const raw = this.cfg.country_filter_list || '';
            const parts = raw.split(',');
            const list = [];
            for (let i = 0; i < parts.length; i += 1) {
                const p = parts[i].trim().toUpperCase();
                if (p !== '') {
                    list.push(p);
                }
            }

            const inList = list.length === 0 ? true : list.indexOf(code) !== -1;

            if (mode === 'whitelist') {
                return inList;
            }
            if (mode === 'blacklist') {
                return !inList;
            }

            return true;
        },

        isValidUrl(url) {
            if (!url) return false;

            try {
                // eslint-disable-next-line no-new
                new URL(url);
                return true;
            } catch (e) {
                return false;
            }
        },

        runTest() {
            const normalizedCountry = (this.testInput.country || '').toUpperCase().trim();
            this.testInput.country = normalizedCountry;

            let decision = 'B';
            let reason = '';

            this.updateMuteStatus();

            const isMuted = this.muteStatus.isMuted;
            const hasRedirect = this.isValidUrl(this.cfg.redirect_url);

            if (!this.cfg.system_on) {
                decision = 'B';
                reason = 'System is OFF';
            } else if (isMuted) {
                decision = 'B';
                reason = 'Auto mute is active';
            } else if (!hasRedirect) {
                decision = 'B';
                reason = 'Redirect URL is missing or invalid';
            } else if (this.testInput.device !== 'mobile') {
                decision = 'B';
                reason = 'Non-mobile device falls back';
            } else if (this.testInput.vpn === 'yes') {
                decision = 'B';
                reason = 'VPN / proxy detected';
            } else if (!this.isCountryAllowed(normalizedCountry)) {
                decision = 'B';
                reason = 'Country not allowed by current filter mode';
            } else {
                decision = 'A';
                reason = 'System ON, auto-mute off, redirect valid, allowed country, mobile device, no VPN';
            }

            this.testResult = {
                decision: decision,
                reason: reason
            };
        },

        fmt(t) {
            return t ? new Date(t * 1000).toLocaleString() : '';
        },

        updateMuteStatus() {
            const currentMinute = Math.floor(Date.now() / 1000 / 60);
            const currentSecond = Math.floor(Date.now() / 1000);
            const cyclePosition = currentMinute % 5;

            this.muteStatus.cyclePosition = cyclePosition;
            this.muteStatus.isMuted = cyclePosition >= 2;

            let secondsInCycle = currentSecond % 300;
            let secondsRemaining;

            if (this.muteStatus.isMuted) {
                secondsRemaining = 300 - secondsInCycle;
                const mins = Math.floor(secondsRemaining / 60);
                const secs = secondsRemaining % 60;
                this.muteStatus.timeRemaining = `Unmute in ${mins}m ${secs}s`;
            } else {
                secondsRemaining = 120 - secondsInCycle;
                const mins = Math.floor(secondsRemaining / 60);
                const secs = secondsRemaining % 60;
                this.muteStatus.timeRemaining = `Mute in ${mins}m ${secs}s`;
            }
        },

        // Environment Config Methods
        async loadEnvConfig() {
            try {
                const r = await fetch('env-config.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ action: 'get' })
                });

                const data = await r.json();

                if (data && data.ok) {
                    this.envConfig = data.config;
                }
            } catch (e) {
                console.error('Failed to load environment config:', e);
            }
        },

        async saveEnvConfig() {
            if (this.isSavingEnv) {
                return;
            }

            this.isSavingEnv = true;

            try {
                const r = await fetch('env-config.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        action: 'update',
                        config: this.envConfig
                    })
                });

                const data = await r.json();

                if (data && data.ok) {
                    this.setFlash('Environment configuration saved successfully', 'info');
                } else {
                    this.setFlash(data.error || 'Failed to save configuration', 'error');
                }
            } catch (e) {
                this.setFlash('Failed to save environment configuration', 'error');
            } finally {
                this.isSavingEnv = false;
            }
        },

        async testDatabaseConnection() {
            if (this.isTestingDb) {
                return;
            }

            this.isTestingDb = true;

            try {
                const r = await fetch('env-config.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        action: 'test_db',
                        host: this.envConfig.SRP_DB_HOST,
                        database: this.envConfig.SRP_DB_NAME,
                        username: this.envConfig.SRP_DB_USER,
                        password: this.envConfig.SRP_DB_PASS
                    })
                });

                const data = await r.json();

                if (data && data.ok) {
                    this.setFlash('Database connection successful', 'info');
                } else {
                    this.setFlash(data.message || 'Database connection failed', 'error');
                }
            } catch (e) {
                this.setFlash('Failed to test database connection', 'error');
            } finally {
                this.isTestingDb = false;
            }
        },

        async testSrpConnection() {
            if (this.isTestingSrp) {
                return;
            }

            this.isTestingSrp = true;

            try {
                const r = await fetch('env-config.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        action: 'test_srp',
                        api_url: this.envConfig.SRP_API_URL,
                        api_key: this.envConfig.SRP_API_KEY
                    })
                });

                const data = await r.json();

                if (data && data.ok) {
                    this.setFlash('SRP API connection successful', 'info');
                } else {
                    this.setFlash(data.message || 'SRP API connection failed', 'error');
                }
            } catch (e) {
                this.setFlash('Failed to test SRP API connection', 'error');
            } finally {
                this.isTestingSrp = false;
            }
        },

        // API Key Fetcher Methods
        getEndpointDescription() {
            const descriptions = {
                'offers': 'Get list of available offers with details',
                'smartlinks': 'View all your smartlinks and configurations',
                'stats_daily': 'Get detailed statistics for a specific day',
                'stats_range': 'View statistics for a custom date range',
                'balance': 'Check your current account balance and earnings',
                'points': 'View your bonus points and rewards',
                'leads': 'Get conversion/lead report for date range',
                'clicks': 'View click report for specified date range'
            };
            return descriptions[this.apiKeyFetcher.endpoint] || 'Select an endpoint';
        },

        updateEndpointParams() {
            // Reset parameters when changing endpoints
            const today = new Date().toISOString().split('T')[0];
            this.apiKeyFetcher.startDate = today;
            this.apiKeyFetcher.endDate = today;
        },

        formatCurrency(value) {
            return '$' + parseFloat(value || 0).toFixed(2);
        },

        formatNumber(value) {
            return parseInt(value || 0).toLocaleString();
        },

        calculateCR() {
            const resp = this.apiKeyFetcher.response;
            if (!resp) return '0%';

            if (resp.cr !== undefined) {
                return parseFloat(resp.cr).toFixed(2) + '%';
            }

            const clicks = parseInt(resp.clicks || 0);
            const conversions = parseInt(resp.conversions || 0);

            if (clicks === 0) return '0%';

            const cr = (conversions / clicks) * 100;
            return cr.toFixed(2) + '%';
        },

        async fetchApiKey() {
            this.apiKeyFetcher.isLoading = true;
            this.apiKeyFetcher.error = '';
            this.apiKeyFetcher.success = false;
            this.apiKeyFetcher.response = null;

            try {
                const payload = {
                    action: this.apiKeyFetcher.endpoint
                };

                // Add parameters based on endpoint
                if (['stats_daily'].includes(this.apiKeyFetcher.endpoint)) {
                    payload.date = this.apiKeyFetcher.startDate;
                } else if (['stats_range', 'leads', 'clicks'].includes(this.apiKeyFetcher.endpoint)) {
                    payload.start_date = this.apiKeyFetcher.startDate;
                    payload.end_date = this.apiKeyFetcher.endDate;
                } else if (this.apiKeyFetcher.endpoint === 'offers') {
                    payload.limit = this.apiKeyFetcher.limit || 50;
                }

                const response = await fetch('/imonetizeit-api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(payload)
                });

                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }

                const result = await response.json();

                if (!result.ok) {
                    throw new Error(result.error || 'API request failed');
                }

                this.apiKeyFetcher.response = result.data;
                this.apiKeyFetcher.success = true;

                this.setFlash('Data fetched successfully!', 'info');

                // Auto-hide success message after 3 seconds
                setTimeout(() => {
                    this.apiKeyFetcher.success = false;
                }, 3000);

            } catch (error) {
                this.apiKeyFetcher.error = error.message || 'Failed to fetch data. Please try again.';
                this.setFlash('Failed to fetch data: ' + error.message, 'error');
            } finally {
                this.apiKeyFetcher.isLoading = false;
            }
        },

        copyApiResponse() {
            if (this.apiKeyFetcher.response) {
                const text = JSON.stringify(this.apiKeyFetcher.response, null, 2);
                navigator.clipboard.writeText(text).then(() => {
                    this.setFlash('API response copied to clipboard!', 'info');
                }).catch(() => {
                    this.apiKeyFetcher.error = 'Failed to copy to clipboard';
                    this.setFlash('Failed to copy to clipboard', 'error');
                });
            }
        }
    }));
});
</script>

<?php require __DIR__ . '/components/footer.php'; ?>
