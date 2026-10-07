{{--
    Notification bell + dropdown (design/screenshots/notifications_1.png, Flow 10). Included in both
    the desktop and the mobile topbar; the data lives in one shared Alpine store
    ($store.notifications, public/js/notifications.js), which polls the feed every 15 seconds.
    Below lg the panel is pinned under the mobile topbar; from lg it hangs off the bell.
--}}
<div x-data="{ open: false }" class="relative" @keydown.escape.window="open = false">
    <button type="button" @click="open = !open; if (open) $store.notifications.refresh()"
            :aria-expanded="open.toString()" aria-haspopup="true"
            class="relative w-9 h-9 rounded-full bg-gray-50 border border-gray-200 flex items-center justify-center text-gray-500 hover:bg-gray-100 hover:text-brand transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-brand/40">
        <i data-lucide="bell" class="w-4 h-4"></i>
        <span class="sr-only">Notifications</span>
        <span x-show="$store.notifications.unread > 0" x-text="$store.notifications.badge" x-cloak
              class="absolute -top-1 -right-1 min-w-4 h-4 px-1 rounded-full bg-brand text-white text-[9px] font-bold flex items-center justify-center tabular-nums"></span>
    </button>

    <div x-show="open" x-cloak @click.outside="open = false"
         x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1"
         class="fixed top-16 inset-x-3 sm:left-auto sm:w-[22rem] lg:absolute lg:top-full lg:right-0 lg:mt-2 z-40 bg-white border border-gray-100 rounded-xl shadow-xl"
         role="dialog" aria-label="Notifications">
        <div class="px-4 pt-4 pb-1">
            <h3 class="inline-flex items-center px-4 py-2 rounded-lg bg-brand/10 text-brand text-sm font-semibold">Notifications</h3>
        </div>

        {{-- x-effect: Lucide only converts icons already on the page, so redraw them after every update. --}}
        <div class="max-h-[60vh] lg:max-h-[26rem] overflow-y-auto px-4"
             x-effect="$store.notifications.groups; $nextTick(() => lucide.createIcons())">
            <template x-for="group in $store.notifications.groups" :key="group.day">
                <section>
                    <h4 class="pt-4 pb-1 text-sm font-semibold text-gray-900" x-text="group.day"></h4>
                    <template x-for="item in group.items" :key="item.id">
                        <a :href="item.url" class="-mx-2 px-2 py-3 flex gap-3 rounded-lg border-b border-gray-100 last:border-b-0 hover:bg-gray-50 transition-colors">
                            <span class="relative w-10 h-10 shrink-0 rounded-full bg-brand/10 text-brand flex items-center justify-center">
                                <i data-lucide="bell" class="w-4 h-4"></i>
                                <span x-show="!item.read" class="absolute bottom-0.5 right-0.5 w-2.5 h-2.5 rounded-full bg-red-500 ring-2 ring-white">
                                    <span class="sr-only">Unread</span>
                                </span>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="flex items-center justify-between gap-2">
                                    <span class="rounded px-1.5 py-0.5 text-[11px] font-medium bg-orange-50 text-orange-700" x-text="item.tag"></span>
                                    <time class="text-[11px] text-gray-500 tabular-nums whitespace-nowrap" :datetime="item.datetime" x-text="item.time"></time>
                                </span>
                                <span class="mt-1 block text-sm leading-snug line-clamp-2 break-words"
                                      :class="item.read ? 'text-gray-600' : 'font-medium text-gray-900'" x-text="item.message"></span>
                            </span>
                        </a>
                    </template>
                </section>
            </template>

            <div x-show="!$store.notifications.groups.length" class="py-10 text-center">
                <div class="w-11 h-11 rounded-xl bg-brand/[0.06] flex items-center justify-center mx-auto mb-3">
                    <i data-lucide="bell-off" class="w-5 h-5 text-brand"></i>
                </div>
                <p class="text-sm font-semibold text-gray-900">You're all caught up</p>
                <p class="text-xs text-gray-500 mt-1">Updates on your tickets will show up here.</p>
            </div>
        </div>

        <div class="mx-4 mt-1 border-t border-gray-100 py-3 text-center">
            <a href="{{ route('notifications.index') }}" class="text-sm font-semibold text-brand hover:text-brand-dark">View all</a>
        </div>
    </div>
</div>
