<!-- API Key Tab -->
<div x-show="activeTab === 'api-key'" x-cloak>
    <div class="space-y-4">
        <!-- API Key Fetcher Card -->
        <div class="card p-4">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h3 class="text-sm font-semibold">iMonetizeIt API Client</h3>
                    <p class="text-[11px] text-muted-foreground mt-1">
                        Interact with iMonetizeIt API endpoints
                    </p>
                </div>
                <svg class="h-5 w-5 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                </svg>
            </div>

            <div class="space-y-3">
                <!-- Endpoint Selection -->
                <div class="space-y-1.5">
                    <label class="text-xs font-medium leading-none">
                        API Endpoint
                    </label>
                    <div class="relative">
                        <select class="input pr-7 appearance-none text-[11px]"
                                x-model="apiKeyFetcher.endpoint"
                                @change="updateEndpointParams()">
                            <option value="offers">Offers List</option>
                            <option value="smartlinks">Smartlinks</option>
                            <option value="stats_daily">Daily Statistics</option>
                            <option value="stats_range">Statistics Range</option>
                            <option value="balance">Account Balance</option>
                            <option value="points">Bonus Points</option>
                            <option value="leads">Leads Report</option>
                            <option value="clicks">Clicks Report</option>
                        </select>
                        <span class="pointer-events-none absolute inset-y-0 right-2 flex items-center">
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </span>
                    </div>
                    <p class="text-[11px] text-muted-foreground" x-text="getEndpointDescription()"></p>
                </div>

                <!-- Date Range Selection (for date-based endpoints) -->
                <div x-show="['stats_daily', 'stats_range', 'leads', 'clicks'].includes(apiKeyFetcher.endpoint)" x-transition class="space-y-1.5">
                    <label class="text-xs font-medium leading-none">
                        <span x-show="apiKeyFetcher.endpoint === 'stats_daily'">Date</span>
                        <span x-show="['stats_range', 'leads', 'clicks'].includes(apiKeyFetcher.endpoint)">Date Range</span>
                    </label>
                    <div class="grid gap-2" :class="apiKeyFetcher.endpoint === 'stats_daily' ? 'grid-cols-1' : 'grid-cols-2'">
                        <input
                            type="date"
                            class="input text-[11px]"
                            x-model="apiKeyFetcher.startDate"
                            :max="new Date().toISOString().split('T')[0]">
                        <input
                            x-show="['stats_range', 'leads', 'clicks'].includes(apiKeyFetcher.endpoint)"
                            type="date"
                            class="input text-[11px]"
                            x-model="apiKeyFetcher.endDate"
                            :max="new Date().toISOString().split('T')[0]">
                    </div>
                    <p class="text-[11px] text-muted-foreground">
                        Select date range for the report (YYYY-MM-DD format)
                    </p>
                </div>

                <!-- Limit Selection (for offers) -->
                <div x-show="apiKeyFetcher.endpoint === 'offers'" x-transition class="space-y-1.5">
                    <label class="text-xs font-medium leading-none">
                        Number of Offers
                    </label>
                    <input
                        type="number"
                        class="input text-[11px]"
                        min="1"
                        max="200"
                        x-model.number="apiKeyFetcher.limit"
                        placeholder="50">
                    <p class="text-[11px] text-muted-foreground">
                        Maximum 200 offers (default: 50)
                    </p>
                </div>

                <!-- Action Button -->
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="fetchApiKey()"
                        class="btn btn-primary btn-sm flex-1"
                        :disabled="apiKeyFetcher.isLoading"
                        data-sniper="1">
                        <svg x-show="apiKeyFetcher.isLoading" class="h-3.5 w-3.5 mr-1.5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" d="M4 12a8 8 0 0 1 8-8" stroke="currentColor" stroke-width="4" stroke-linecap="round"></path>
                        </svg>
                        <svg x-show="!apiKeyFetcher.isLoading" class="h-3.5 w-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <span x-text="apiKeyFetcher.isLoading ? 'Fetching...' : 'Fetch Data'"></span>
                    </button>
                </div>
            </div>

            <!-- Error Message -->
            <div x-show="apiKeyFetcher.error"
                 x-transition
                 class="mt-3 p-3 rounded-lg bg-red-50 border border-red-200">
                <div class="flex items-start gap-2">
                    <svg class="h-4 w-4 text-red-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div class="flex-1">
                        <p class="text-xs font-medium text-red-900">Error</p>
                        <p class="text-[11px] text-red-700 mt-0.5" x-text="apiKeyFetcher.error"></p>
                    </div>
                </div>
            </div>

            <!-- Success Message -->
            <div x-show="apiKeyFetcher.success"
                 x-transition
                 class="mt-3 p-3 rounded-lg bg-emerald-50 border border-emerald-200">
                <div class="flex items-start gap-2">
                    <svg class="h-4 w-4 text-emerald-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div class="flex-1">
                        <p class="text-xs font-medium text-emerald-900">Success</p>
                        <p class="text-[11px] text-emerald-700 mt-0.5">API key fetched successfully!</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards (for stats/balance/points endpoints) -->
        <div x-show="apiKeyFetcher.response && ['stats_daily', 'stats_range', 'balance', 'points'].includes(apiKeyFetcher.endpoint)"
             x-transition
             class="space-y-4">
            <!-- Summary Cards -->
            <div class="grid gap-3 grid-cols-2 md:grid-cols-3">
                <!-- Total Earnings Card -->
                <div class="card p-3 text-center" x-show="apiKeyFetcher.response?.earnings !== undefined">
                    <div class="inline-flex items-center justify-center gap-1.5 mb-1.5">
                        <svg class="h-3.5 w-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h3 class="text-[11px] font-medium text-muted-foreground uppercase">Earnings</h3>
                    </div>
                    <div class="text-xl font-semibold leading-tight" x-text="formatCurrency(apiKeyFetcher.response?.earnings || 0)"></div>
                    <p class="text-[10px] text-muted-foreground mt-0.5" x-text="apiKeyFetcher.timePeriod || 'All time'"></p>
                </div>

                <!-- Balance Card -->
                <div class="card p-3 text-center" x-show="apiKeyFetcher.response?.balance !== undefined">
                    <div class="inline-flex items-center justify-center gap-1.5 mb-1.5">
                        <svg class="h-3.5 w-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                        </svg>
                        <h3 class="text-[11px] font-medium text-muted-foreground uppercase">Balance</h3>
                    </div>
                    <div class="text-xl font-semibold leading-tight" x-text="formatCurrency(apiKeyFetcher.response?.balance || 0)"></div>
                    <p class="text-[10px] text-muted-foreground mt-0.5">Available</p>
                </div>

                <!-- Bonus Points Card -->
                <div class="card p-3 text-center" x-show="apiKeyFetcher.response?.points !== undefined || apiKeyFetcher.response?.bonus_points !== undefined">
                    <div class="inline-flex items-center justify-center gap-1.5 mb-1.5">
                        <svg class="h-3.5 w-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                        </svg>
                        <h3 class="text-[11px] font-medium text-muted-foreground uppercase">Bonus Points</h3>
                    </div>
                    <div class="text-xl font-semibold leading-tight" x-text="formatNumber(apiKeyFetcher.response?.points || apiKeyFetcher.response?.bonus_points || 0)"></div>
                    <p class="text-[10px] text-muted-foreground mt-0.5">Total points</p>
                </div>

                <!-- Conversions Card -->
                <div class="card p-3 text-center" x-show="apiKeyFetcher.response?.conversions !== undefined">
                    <div class="inline-flex items-center justify-center gap-1.5 mb-1.5">
                        <svg class="h-3.5 w-3.5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h3 class="text-[11px] font-medium text-muted-foreground uppercase">Conversions</h3>
                    </div>
                    <div class="text-xl font-semibold leading-tight" x-text="formatNumber(apiKeyFetcher.response?.conversions || 0)"></div>
                    <p class="text-[10px] text-muted-foreground mt-0.5" x-text="apiKeyFetcher.timePeriod || 'Total'"></p>
                </div>

                <!-- Clicks Card -->
                <div class="card p-3 text-center" x-show="apiKeyFetcher.response?.clicks !== undefined">
                    <div class="inline-flex items-center justify-center gap-1.5 mb-1.5">
                        <svg class="h-3.5 w-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path>
                        </svg>
                        <h3 class="text-[11px] font-medium text-muted-foreground uppercase">Clicks</h3>
                    </div>
                    <div class="text-xl font-semibold leading-tight" x-text="formatNumber(apiKeyFetcher.response?.clicks || 0)"></div>
                    <p class="text-[10px] text-muted-foreground mt-0.5" x-text="apiKeyFetcher.timePeriod || 'Total'"></p>
                </div>

                <!-- CR% Card -->
                <div class="card p-3 text-center" x-show="apiKeyFetcher.response?.cr !== undefined || (apiKeyFetcher.response?.clicks && apiKeyFetcher.response?.conversions)">
                    <div class="inline-flex items-center justify-center gap-1.5 mb-1.5">
                        <svg class="h-3.5 w-3.5 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                        <h3 class="text-[11px] font-medium text-muted-foreground uppercase">CR%</h3>
                    </div>
                    <div class="text-xl font-semibold leading-tight" x-text="calculateCR()"></div>
                    <p class="text-[10px] text-muted-foreground mt-0.5">Conversion Rate</p>
                </div>
            </div>
        </div>

        <!-- Raw API Response Display -->
        <div x-show="apiKeyFetcher.response"
             x-transition
             class="card p-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold">Raw API Response</h3>
                <button
                    type="button"
                    @click="copyApiResponse()"
                    class="btn btn-ghost btn-sm"
                    title="Copy to clipboard">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>
                    Copy
                </button>
            </div>

            <div class="space-y-2">
                <!-- Response Data -->
                <div class="relative">
                    <pre class="bg-muted rounded-lg p-3 overflow-x-auto text-[11px] font-mono max-h-[400px] scroll-logs"
                         x-text="JSON.stringify(apiKeyFetcher.response, null, 2)"></pre>
                </div>
            </div>
        </div>

        <!-- Usage Instructions -->
        <div class="card p-4">
            <h3 class="text-sm font-semibold mb-3">Usage Instructions</h3>
            <div class="space-y-2 text-xs text-muted-foreground">
                <div class="flex items-start gap-2">
                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-muted text-[10px] font-medium mt-0.5">1</span>
                    <p>Select the API endpoint you want to use (Get Access Token, Create Smartlink, Edit Smartlink, or Custom)</p>
                </div>
                <div class="flex items-start gap-2">
                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-muted text-[10px] font-medium mt-0.5">2</span>
                    <p>Enter your iMonetizeIt API key, or click "Use Default" to use the pre-configured key</p>
                </div>
                <div class="flex items-start gap-2">
                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-muted text-[10px] font-medium mt-0.5">3</span>
                    <p>For Smartlink endpoints, customize the JSON request body with your parameters (subvertical_id, name, etc.)</p>
                </div>
                <div class="flex items-start gap-2">
                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-muted text-[10px] font-medium mt-0.5">4</span>
                    <p>Click "Send Request" to execute the API call and view the response in JSON format below</p>
                </div>
                <div class="flex items-start gap-2">
                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-muted text-[10px] font-medium mt-0.5">5</span>
                    <p>Use the "Copy" button to copy the API response to your clipboard for further processing</p>
                </div>
            </div>
        </div>

        <!-- API Documentation Link -->
        <div class="card p-4 bg-blue-50 border-blue-200">
            <div class="flex items-start gap-2">
                <svg class="h-4 w-4 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div class="flex-1">
                    <p class="text-xs font-medium text-blue-900 mb-1">API Documentation</p>
                    <p class="text-[11px] text-blue-700">
                        For complete API documentation and all available methods, visit the
                        <a href="https://app.swaggerhub.com/apis-docs/Imonetizeit7/api/1.0.4"
                           target="_blank"
                           class="underline font-medium hover:text-blue-900">
                            iMonetizeIt API Documentation on SwaggerHub
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
