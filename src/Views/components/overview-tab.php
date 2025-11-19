<!-- Overview Tab -->
<div x-show="activeTab === 'overview'" x-cloak>
    <div class="space-y-4">
        <!-- Statistics Cards -->
        <div class="grid gap-3 grid-cols-2 md:grid-cols-4">
            <!-- Total Requests -->
            <div class="card p-3 text-center">
                <div class="inline-flex items-center justify-center gap-1.5 mb-1.5">
                    <img src="/assets/icons/fox-head.svg" alt="Fox head logo" class="h-3.5 w-3.5" width="14" height="14">
                    <h3 class="text-[11px] font-medium text-muted-foreground uppercase tracking-wide">Total</h3>
                </div>
                <div class="text-xl font-semibold leading-tight" x-text="logs.length"></div>
                <p class="text-[10px] text-muted-foreground mt-0.5">Last 50 records</p>
            </div>

            <!-- Redirected A -->
            <div class="card p-3 text-center">
                <div class="inline-flex items-center justify-center gap-1.5 mb-1.5">
                    <svg class="h-3.5 w-3.5 text-emerald-500/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"></path>
                    </svg>
                    <h3 class="text-[11px] font-medium text-muted-foreground uppercase tracking-wide">Redirect A</h3>
                </div>
                <div class="text-xl font-semibold leading-tight"
                     x-text="logs.filter(l => l.decision === 'A').length"></div>
                <p class="text-[10px] text-muted-foreground mt-0.5">Decision A</p>
            </div>

            <!-- Fallback B -->
            <div class="card p-3 text-center">
                <div class="inline-flex items-center justify-center gap-1.5 mb-1.5">
                    <svg class="h-3.5 w-3.5 text-amber-500/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"></path>
                    </svg>
                    <h3 class="text-[11px] font-medium text-muted-foreground uppercase tracking-wide">Fallback B</h3>
                </div>
                <div class="text-xl font-semibold leading-tight"
                     x-text="logs.filter(l => l.decision === 'B').length"></div>
                <p class="text-[10px] text-muted-foreground mt-0.5">Desktop / Bot / VPN</p>
            </div>

            <!-- System summary -->
            <div class="card p-3 text-center">
                <div class="inline-flex items-center justify-center gap-1.5 mb-1.5">
                    <span class="inline-flex h-2 w-2 rounded-full"
                          :class="cfg.system_on ? (muteStatus.isMuted ? 'bg-amber-500' : 'bg-emerald-500') : 'bg-black'"></span>
                    <h3 class="text-[11px] font-medium text-muted-foreground uppercase tracking-wide">System</h3>
                </div>
                <div class="text-xl font-semibold leading-tight"
                     x-text="cfg.system_on ? (muteStatus.isMuted ? 'MUTED' : 'ACTIVE') : 'OFF'"></div>
                <p class="text-[10px] text-muted-foreground mt-0.5"
                   x-text="cfg.system_on ? muteStatus.timeRemaining : 'System disabled'"></p>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card p-4">
            <h3 class="text-sm font-semibold mb-3">Quick Actions</h3>
            <div class="grid gap-2 grid-cols-1 md:grid-cols-3">
                <button
                    type="button"
                    @click="activeTab = 'routing'"
                    class="btn text-left justify-start">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Configure Routing
                </button>

                <button
                    type="button"
                    @click="activeTab = 'env-config'"
                    class="btn text-left justify-start">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>
                    </svg>
                    Environment Settings
                </button>

                <button
                    type="button"
                    @click="activeTab = 'logs'"
                    class="btn text-left justify-start">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    View Traffic Logs
                </button>
            </div>
        </div>

        <!-- System Status -->
        <div class="card p-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold">System Status</h3>
                <button
                    type="button"
                    @click="cfg.system_on = !cfg.system_on; save()"
                    class="btn btn-sm"
                    :class="cfg.system_on ? 'btn-primary' : 'btn-outline'"
                    :disabled="isSavingCfg"
                    data-sniper="1">
                    <svg x-show="isSavingCfg" class="h-3 w-3 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" d="M4 12a8 8 0 0 1 8-8" stroke="currentColor" stroke-width="4" stroke-linecap="round"></path>
                    </svg>
                    <span x-text="isSavingCfg ? 'Saving...' : (cfg.system_on ? 'Turn Off' : 'Turn On')"></span>
                </button>
            </div>

            <div class="space-y-2 text-xs">
                <div class="flex justify-between py-2 border-b">
                    <span class="text-muted-foreground">Status</span>
                    <span class="font-medium"
                          :class="cfg.system_on ? (muteStatus.isMuted ? 'text-amber-600' : 'text-emerald-600') : 'text-muted-foreground'"
                          x-text="cfg.system_on ? (muteStatus.isMuted ? 'Muted' : 'Active') : 'Offline'"></span>
                </div>
                <div class="flex justify-between py-2 border-b" x-show="cfg.system_on">
                    <span class="text-muted-foreground">Redirect URL</span>
                    <span class="font-medium truncate ml-2 max-w-xs" x-text="cfg.redirect_url || 'Not set'"></span>
                </div>
                <div class="flex justify-between py-2 border-b">
                    <span class="text-muted-foreground">Total Logs</span>
                    <span class="font-medium" x-text="logs.length + ' / 50'"></span>
                </div>
                <div class="flex justify-between py-2" x-show="cfg.system_on">
                    <span class="text-muted-foreground">Cycle Status</span>
                    <span class="font-medium" x-text="muteStatus.timeRemaining"></span>
                </div>
            </div>
        </div>
    </div>
</div>
