<!-- Routing Configuration Tab -->
<div x-show="activeTab === 'routing'" x-cloak>
    <div class="space-y-4">
        <!-- Configuration Form -->
        <div class="card p-4">
            <div class="flex items-center justify-between gap-3 mb-4">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <h3 class="font-semibold tracking-tight text-sm">Routing Configuration</h3>
                        <span x-show="cfg.system_on"
                              class="badge text-[10px] px-1.5 py-0.5"
                              :class="muteStatus.isMuted ? 'badge-secondary' : 'badge-default'"
                              x-text="muteStatus.isMuted ? '🔇 Muted' : '🔊 Active'"></span>
                    </div>
                    <p class="text-[11px] text-muted-foreground">
                        <span x-show="!cfg.system_on">System toggle, redirect target, and country filters</span>
                        <span x-show="cfg.system_on" x-text="'Auto cycle: ' + muteStatus.timeRemaining"></span>
                    </p>
                </div>
                <button type="button"
                        @click="cfg.system_on = !cfg.system_on; save()"
                        class="btn btn-sm"
                        :class="cfg.system_on ? 'btn-default' : 'btn-outline'"
                        :disabled="isSavingCfg"
                        data-sniper="1">
                    <svg x-show="!isSavingCfg"
                         class="h-3.5 w-3.5 text-muted-foreground"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="m16 10 3-3m0 0-3-3m3 3H5v3m3 4-3 3m0 0 3 3m-3-3h14v-3"/>
                    </svg>
                    <svg x-show="isSavingCfg"
                         class="h-3.5 w-3.5 mr-1.5 animate-spin"
                         fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75"
                              d="M4 12a8 8 0 0 1 8-8"
                              stroke="currentColor" stroke-width="4" stroke-linecap="round"></path>
                    </svg>
                    <span class="text-xs"
                          x-text="isSavingCfg ? 'Saving...' : (cfg.system_on ? 'Turn Off' : 'Turn On')"></span>
                </button>
            </div>

            <div class="space-y-3">
                <!-- Redirect URL -->
                <div class="space-y-1.5">
                    <label class="text-xs font-medium leading-none">
                        Redirect URL
                    </label>
                    <input type="url" class="input" placeholder="https://example.com"
                           x-model="cfg.redirect_url"
                           @input.debounce.400ms="save()">
                    <p class="text-[11px] text-muted-foreground">Must use HTTPS protocol</p>
                </div>

                <!-- Country Filter Mode -->
                <div class="space-y-1.5">
                    <label class="text-xs font-medium leading-none">
                        Country Filter Mode
                    </label>
                    <div class="relative">
                        <select class="input pr-7 appearance-none"
                                x-model="cfg.country_filter_mode"
                                @change="save()">
                            <option value="all">All Countries</option>
                            <option value="whitelist">Whitelist Only</option>
                            <option value="blacklist">Blacklist Only</option>
                        </select>
                        <span class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-[10px] text-muted-foreground">
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </span>
                    </div>
                </div>

                <!-- Country List -->
                <div x-show="cfg.country_filter_mode !== 'all'"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     class="space-y-1.5">
                    <label class="text-xs font-medium leading-none">
                        <span
                            x-text="cfg.country_filter_mode === 'whitelist' ? 'Allowed Countries' : 'Blocked Countries'"></span>
                    </label>
                    <textarea class="textarea font-mono text-[11px] scroll-logs" rows="3"
                              placeholder="US, GB, ID, AU, CA"
                              x-model="cfg.country_filter_list"
                              @input.debounce.400ms="save()"></textarea>
                    <p class="text-[11px] text-muted-foreground">ISO Alpha-2 codes, comma separated</p>
                </div>
            </div>

            <div class="pt-3 mt-3 border-t">
                <div class="text-[11px] text-muted-foreground">
                    Last updated:
                    <span class="font-medium" x-text="fmt(cfg.updated_at)"></span>
                </div>
            </div>
        </div>

        <!-- Decision Tester -->
        <div class="card">
            <div class="p-4 space-y-3">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="font-semibold tracking-tight text-sm">Decision Tester</h3>
                        <p class="text-[11px] text-muted-foreground">
                            Simulate redirect decision based on current configuration
                        </p>
                    </div>
                    <button type="button"
                            class="btn btn-sm btn-ghost"
                            @click="testerOpen = !testerOpen">
                        <span class="text-[11px]" x-text="testerOpen ? 'Hide' : 'Show'"></span>
                    </button>
                </div>

                <div x-show="testerOpen"
                     x-transition
                     class="space-y-3">
                    <div class="grid gap-3 md:grid-cols-3">
                        <!-- Country code -->
                        <div class="space-y-1.5">
                            <label class="text-xs font-medium leading-none">
                                Country (ISO Alpha-2)
                            </label>
                            <input type="text"
                                   class="input font-mono up-text text-[11px]"
                                   placeholder="e.g. ID"
                                   maxlength="2"
                                   x-model="testInput.country">
                            <p class="text-[11px] text-muted-foreground">
                                Will be uppercased automatically
                            </p>
                        </div>

                        <!-- Device -->
                        <div class="space-y-1.5">
                            <label class="text-xs font-medium leading-none">
                                Device
                            </label>
                            <div class="relative">
                                <select class="input pr-7 appearance-none" x-model="testInput.device">
                                    <option value="mobile">Mobile / WAP</option>
                                    <option value="desktop">Desktop / WEB</option>
                                </select>
                                <span class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-[10px] text-muted-foreground">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </span>
                            </div>
                            <p class="text-[11px] text-muted-foreground">
                                Simplified device bucket
                            </p>
                        </div>

                        <!-- VPN -->
                        <div class="space-y-1.5">
                            <label class="text-xs font-medium leading-none">
                                VPN / Proxy
                            </label>
                            <div class="relative">
                                <select class="input pr-7 appearance-none" x-model="testInput.vpn">
                                    <option value="no">No VPN detected</option>
                                    <option value="yes">VPN / Proxy</option>
                                </select>
                                <span class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-[10px] text-muted-foreground">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </span>
                            </div>
                            <p class="text-[11px] text-muted-foreground">
                                Assumed detection result
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-1">
                        <button type="button"
                                class="btn btn-default btn-sm"
                                @click="runTest()">
                            Run Test
                        </button>

                        <template x-if="testResult">
                            <div class="text-right text-[11px] space-y-0.5">
                                <div>
                                    Decision:
                                    <span class="badge"
                                          :class="testResult.decision === 'A' ? 'badge-default' : 'badge-secondary'"
                                          x-text="testResult.decision === 'A' ? 'Redirect (A)' : 'Fallback (B)'">
                                    </span>
                                </div>
                                <div class="text-muted-foreground max-w-xs md:max-w-sm"
                                     x-text="testResult.reason"></div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
