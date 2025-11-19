<!-- Traffic Logs Tab -->
<div x-show="activeTab === 'logs'" x-cloak>
    <div class="card">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 p-4 border-b">
            <div class="space-y-0.5">
                <h3 class="font-semibold tracking-tight text-sm">Traffic Logs</h3>
                <p class="text-[12px] text-muted-foreground">Real-time traffic monitoring</p>
            </div>
            <button @click="clearLogs"
                    class="btn btn-default btn-sm"
                    :disabled="isClearingLogs"
                    data-sniper="1">
                <svg x-show="!isClearingLogs"
                     class="h-3.5 w-3.5 mr-1.5"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="m15 9-6 6m0-6 6 6m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
                <svg x-show="isClearingLogs"
                     class="h-3.5 w-3.5 mr-1.5 animate-spin"
                     fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10"
                            stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75"
                          d="M4 12a8 8 0 0 1 8-8"
                          stroke="currentColor" stroke-width="4" stroke-linecap="round"></path>
                </svg>
                <span x-text="isClearingLogs ? 'Clearing...' : 'Clear All'"></span>
            </button>
        </div>

        <div class="relative overflow-x-auto overflow-y-auto max-h-[420px] scroll-logs">
            <table class="w-full text-[12px]">
                <thead class="border-b bg-white sticky top-0 z-10">
                <tr>
                    <th class="h-8 px-3 text-left align-middle font-medium text-muted-foreground">
                        Click ID
                    </th>
                    <th class="h-8 px-3 text-left align-middle font-medium text-muted-foreground hidden lg:table-cell">
                        IP Address
                    </th>
                    <th class="h-8 px-3 text-left align-middle font-medium text-muted-foreground">
                        Country
                    </th>
                    <th class="h-8 px-3 text-left align-middle font-medium text-muted-foreground">
                        Decision
                    </th>
                    <th class="h-8 px-3 text-left align-middle font-medium text-muted-foreground hidden md:table-cell">
                        User Agent
                    </th>
                </tr>
                </thead>
                <tbody class="[&_tr:last-child]:border-0">
                <template x-if="logs.length === 0">
                    <tr class="border-b">
                        <td colspan="5" class="p-3 text-center text-[11px] text-muted-foreground">
                            No traffic logs yet.
                        </td>
                    </tr>
                </template>
                <template x-for="r in logs" :key="r.id">
                    <tr class="border-b transition-colors hover:bg-muted/50"
                        :class="r.decision === 'A'
                            ? 'bg-emerald-50/40'
                            : (r.decision === 'B' ? 'bg-slate-50' : '')">
                        <td class="p-2 align-middle">
                            <code
                                class="relative rounded bg-muted px-1 py-[0.1rem] font-mono text-[11px] font-semibold"
                                x-text="r.click_id || '-'"></code>
                        </td>
                        <td class="p-2 align-middle hidden lg:table-cell">
                            <code
                                class="relative rounded bg-muted px-1 py-[0.1rem] font-mono text-[11px] text-muted-foreground"
                                x-text="r.ip"></code>
                        </td>
                        <td class="p-2 align-middle">
                            <span class="badge badge-outline text-[11px]" x-text="r.country_code || 'XX'"></span>
                        </td>
                        <td class="p-2 align-middle">
                            <span class="badge text-[11px]"
                                  :class="r.decision === 'A' ? 'badge-default' : 'badge-secondary'"
                                  x-text="r.decision === 'A' ? 'Redirect' : 'Fallback'"></span>
                        </td>
                        <td class="p-2 align-middle hidden md:table-cell">
                            <span
                                class="text-[11px] text-muted-foreground max-w-md truncate block up-text"
                                x-text="r.ua"></span>
                        </td>
                    </tr>
                </template>
                </tbody>
            </table>
        </div>
    </div>
</div>
