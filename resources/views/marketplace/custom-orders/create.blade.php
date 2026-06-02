<x-layouts.marketplace>
    <section class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="rounded-[2rem] border border-white/10 bg-white/[0.04] p-6 shadow-2xl shadow-black/30 sm:p-8">
            <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-300">{{ __('custom_orders.new_order') }}</p>
            <h1 class="mt-3 text-3xl font-black tracking-tight text-white sm:text-5xl">{{ __('custom_orders.create_title') }}</h1>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-zinc-400">{{ __('custom_orders.create_hint') }}</p>
        </div>

        <form method="POST" action="{{ route('custom-orders.store') }}" enctype="multipart/form-data" class="mt-8 grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
            @csrf
            @if($author)
                <input type="hidden" name="author_id" value="{{ $author->id }}">
            @endif

            <div class="grid gap-6">
                <div class="rounded-3xl border border-white/10 bg-white/[0.04] p-6">
                    <h2 class="text-xl font-black text-white">{{ __('Деталі задачі') }}</h2>
                    <div class="mt-5 grid gap-4">
                        <x-admin.field name="title" :label="__('Назва замовлення')" :value="old('title')" required />
                        <x-admin.field name="description" as="textarea" rows="8" :label="__('Опис')" :value="old('description')" required :helper="__('Опишіть результат, формат файлів, референси, розміри та обмеження.')" />
                        <div class="grid gap-4 md:grid-cols-2">
                            <x-admin.field name="type" as="select" :label="__('Тип')" required>
                                <option value="model_creation" @selected(old('type') === 'model_creation')>{{ __('custom_orders.types.model_creation') }}</option>
                                <option value="print_service" @selected(old('type') === 'print_service')>{{ __('custom_orders.types.print_service') }}</option>
                            </x-admin.field>
                            <x-admin.field name="category_id" as="select" :label="__('Категорія')">
                                <option value="">{{ __('Не вибрано') }}</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>{{ $category->name_uk ?? $category->name }}</option>
                                @endforeach
                            </x-admin.field>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-white/10 bg-white/[0.04] p-6">
                    <h2 class="text-xl font-black text-white">{{ __('Бюджет і терміни') }}</h2>
                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <x-admin.field name="budget_amount" type="number" step="0.01" min="0" :label="__('Бюджет, UAH')" :value="old('budget_amount')" />
                        <x-admin.field name="deadline_at" type="date" :label="__('Бажаний термін')" :value="old('deadline_at')" />
                    </div>
                    <label class="mt-4 flex items-start gap-3 rounded-2xl border border-white/10 bg-zinc-950/50 p-4">
                        <input type="hidden" name="budget_is_negotiable" value="0">
                        <input type="checkbox" name="budget_is_negotiable" value="1" @checked(old('budget_is_negotiable', true)) class="mt-1 rounded border-white/20 bg-zinc-950 text-emerald-400">
                        <span>
                            <span class="block text-sm font-bold text-white">{{ __('Бюджет можна обговорити') }}</span>
                            <span class="mt-1 block text-xs text-zinc-500">{{ __('Автор зможе запропонувати точну ціну після уточнення деталей.') }}</span>
                        </span>
                    </label>
                </div>

                <div class="rounded-3xl border border-white/10 bg-white/[0.04] p-6">
                    <h2 class="text-xl font-black text-white">{{ __('Параметри друку та доставка') }}</h2>
                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <x-admin.field name="quantity" type="number" min="1" :label="__('Кількість')" :value="old('quantity')" />
                        <x-admin.field name="dimensions" :label="__('Розміри')" :value="old('dimensions')" placeholder="120x80x40 мм" />
                        <x-admin.field name="material" :label="__('Матеріал')" :value="old('material')" placeholder="PLA, PETG, resin..." />
                        <x-admin.field name="color" :label="__('Колір')" :value="old('color')" />
                        <x-admin.field name="delivery_service" :label="__('Служба доставки')" :value="old('delivery_service')" placeholder="Нова Пошта / Укрпошта" />
                        <x-admin.field name="delivery_address" :label="__('Адреса / відділення')" :value="old('delivery_address')" />
                    </div>
                    <div class="mt-4">
                        <x-admin.field name="extra_comment" as="textarea" rows="4" :label="__('Додатковий коментар')" :value="old('extra_comment')" />
                    </div>
                </div>

                <div class="rounded-3xl border border-dashed border-emerald-300/25 bg-emerald-300/[0.05] p-6">
                    <h2 class="text-xl font-black text-white">{{ __('Файли та референси') }}</h2>
                    <p class="mt-2 text-sm text-zinc-400">{{ __('Додайте зображення, STL/OBJ/GLB/3MF, ZIP, PDF або текстові файли. Максимум 50MB на файл.') }}</p>
                    <input type="file" name="files[]" multiple class="mt-5 block w-full rounded-2xl border border-white/10 bg-zinc-950/70 px-4 py-4 text-sm text-zinc-300 file:mr-4 file:rounded-xl file:border-0 file:bg-emerald-400 file:px-4 file:py-2 file:text-sm file:font-black file:text-zinc-950">
                </div>
            </div>

            <aside class="self-start rounded-3xl border border-white/10 bg-zinc-950/70 p-5 shadow-2xl shadow-black/30 lg:sticky lg:top-28">
                <p class="text-sm font-black text-white">{{ __('Безпечний процес') }}</p>
                <div class="mt-4 grid gap-3 text-sm text-zinc-400">
                    <p>✓ {{ __('Умови узгоджуються в чаті') }}</p>
                    <p>✓ {{ __('Оплата заморожується в escrow') }}</p>
                    <p>✓ {{ __('Автор отримує кошти після підтвердження') }}</p>
                    <p>✓ {{ __('Для проблем є спір та арбітраж') }}</p>
                </div>
                @if($author)
                    <div class="mt-5 rounded-2xl border border-white/10 bg-white/[0.04] p-4">
                        <p class="text-xs text-zinc-500">{{ __('Автор') }}</p>
                        <p class="mt-1 font-black text-white">{{ $author->displayName() }}</p>
                    </div>
                @endif
                <button class="mt-5 h-12 w-full rounded-2xl bg-emerald-400 text-sm font-black text-zinc-950 shadow-lg shadow-emerald-500/20 hover:bg-emerald-300">{{ __('custom_orders.new_order') }}</button>
            </aside>
        </form>
    </section>
</x-layouts.marketplace>
