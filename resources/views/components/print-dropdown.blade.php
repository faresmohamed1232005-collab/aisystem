@props(['a4Url', 'receiptUrl'])

<div class="print-dropdown relative inline-block text-right">
    <button type="button" class="print-dropdown-trigger inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 shadow-sm transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-300"
            aria-label="خيارات الطباعة" title="خيارات الطباعة" aria-haspopup="menu" aria-expanded="false">
        <i class="fas fa-print" aria-hidden="true"></i>
        <span class="sr-only">خيارات الطباعة</span>
    </button>

    <div class="print-dropdown-menu fixed z-[9999] hidden w-48 overflow-hidden rounded-xl border border-gray-200 bg-white py-1 shadow-xl"
         role="menu">
        <a href="{{ $receiptUrl }}" target="_blank" rel="noopener" role="menuitem"
           class="flex items-center gap-3 px-3 py-2.5 text-xs font-semibold text-gray-700 transition hover:bg-green-50 hover:text-green-700 focus:bg-green-50 focus:text-green-700 focus:outline-none">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-green-100 text-green-700">
                <i class="fas fa-receipt" aria-hidden="true"></i>
            </span>
            <span><span class="block">Receipt</span><span class="font-normal text-gray-400">حراري 80mm</span></span>
        </a>
        <a href="{{ $a4Url }}" target="_blank" rel="noopener" role="menuitem"
           class="flex items-center gap-3 px-3 py-2.5 text-xs font-semibold text-gray-700 transition hover:bg-indigo-50 hover:text-indigo-700 focus:bg-indigo-50 focus:text-indigo-700 focus:outline-none">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700">
                <i class="fas fa-file-alt" aria-hidden="true"></i>
            </span>
            <span><span class="block">A4</span><span class="font-normal text-gray-400">فاتورة ورقية</span></span>
        </a>
    </div>
</div>

@once
    <script>
        function closePrintDropdown(dropdown, restoreFocus = false) {
            const menu = dropdown.querySelector('.print-dropdown-menu');
            const trigger = dropdown.querySelector('.print-dropdown-trigger');
            menu.classList.add('hidden');
            trigger.setAttribute('aria-expanded', 'false');
            if (restoreFocus) trigger.focus();
        }

        function positionPrintMenu(trigger, menu) {
            const rect = trigger.getBoundingClientRect();
            const width = 192;
            const left = Math.max(8, Math.min(window.innerWidth - width - 8, rect.right - width));
            const spaceBelow = window.innerHeight - rect.bottom;
            const top = spaceBelow >= 150 ? rect.bottom + 6 : Math.max(8, rect.top - 142);
            menu.style.left = `${left}px`;
            menu.style.top = `${top}px`;
        }

        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('.print-dropdown-trigger');

            document.querySelectorAll('.print-dropdown').forEach(dropdown => {
                if (trigger && dropdown.contains(trigger)) return;
                closePrintDropdown(dropdown);
            });

            if (!trigger) return;

            const dropdown = trigger.closest('.print-dropdown');
            const menu = dropdown.querySelector('.print-dropdown-menu');
            const opening = menu.classList.contains('hidden');
            menu.classList.toggle('hidden', !opening);
            trigger.setAttribute('aria-expanded', opening ? 'true' : 'false');

            if (opening) {
                positionPrintMenu(trigger, menu);
                menu.querySelector('[role="menuitem"]')?.focus();
            }
        });

        document.addEventListener('keydown', function (event) {
            const dropdown = event.target.closest('.print-dropdown');
            if (!dropdown) return;

            const menu = dropdown.querySelector('.print-dropdown-menu');
            const items = [...menu.querySelectorAll('[role="menuitem"]')];

            if (event.key === 'Escape') {
                closePrintDropdown(dropdown, true);
                return;
            }

            if (!['ArrowDown', 'ArrowUp'].includes(event.key) || menu.classList.contains('hidden')) return;
            event.preventDefault();
            const current = items.indexOf(document.activeElement);
            const next = event.key === 'ArrowDown'
                ? (current + 1) % items.length
                : (current - 1 + items.length) % items.length;
            items[next].focus();
        });

        window.addEventListener('resize', () => document.querySelectorAll('.print-dropdown').forEach(dropdown => closePrintDropdown(dropdown)));
        window.addEventListener('scroll', () => document.querySelectorAll('.print-dropdown').forEach(dropdown => closePrintDropdown(dropdown)), true);
    </script>
@endonce
