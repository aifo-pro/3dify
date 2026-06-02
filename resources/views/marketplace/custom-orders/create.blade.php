<x-layouts.marketplace>
    <section class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="rounded-[2rem] border border-white/10 bg-white/[0.04] p-6 shadow-2xl shadow-black/30 sm:p-8">
            <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-300">{{ __('custom_orders.new_order') }}</p>
            <h1 class="mt-3 text-3xl font-black tracking-tight text-white sm:text-5xl">{{ __('custom_orders.create_title') }}</h1>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-zinc-400">{{ __('custom_orders.create_hint') }}</p>
        </div>

        <form
            method="POST"
            action="{{ route('custom-orders.store') }}"
            enctype="multipart/form-data"
            x-data="{ type: @js(old('type', \App\Models\CustomOrder::TYPE_MODEL_CREATION)), get isPrint() { return this.type === @js(\App\Models\CustomOrder::TYPE_PRINT_SERVICE) } }"
            class="mt-8 grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]"
        >
            @csrf
            @if($author)
                <input type="hidden" name="author_id" value="{{ $author->id }}">
            @endif

            <div class="grid gap-6">
                <div class="rounded-3xl border border-white/10 bg-white/[0.04] p-6">
                    <h2 class="text-xl font-black text-white">{{ __('custom_orders.form.task_details') }}</h2>
                    <div class="mt-5 grid gap-4">
                        <x-admin.field name="title" :label="__('custom_orders.form.order_title')" :value="old('title')" required />
                        <x-admin.field name="description" as="textarea" rows="8" :label="__('custom_orders.form.description')" :value="old('description')" required :helper="__('custom_orders.form.description_helper')" />
                        <div class="grid gap-4 md:grid-cols-2">
                            <x-admin.field name="type" as="select" :label="__('custom_orders.form.type')" x-model="type" required>
                                <option value="model_creation" @selected(old('type') === 'model_creation')>{{ __('custom_orders.types.model_creation') }}</option>
                                <option value="print_service" @selected(old('type') === 'print_service')>{{ __('custom_orders.types.print_service') }}</option>
                            </x-admin.field>
                            <x-admin.field name="category_id" as="select" :label="__('custom_orders.form.category')">
                                <option value="">{{ __('custom_orders.form.not_selected') }}</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>{{ $category->localized('name') ?: $category->slug }}</option>
                                @endforeach
                            </x-admin.field>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-white/10 bg-white/[0.04] p-6">
                    <h2 class="text-xl font-black text-white">{{ __('custom_orders.form.budget_deadline') }}</h2>
                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <x-admin.field name="budget_amount" type="number" step="0.01" min="0" :label="__('custom_orders.form.budget_uah')" :value="old('budget_amount')" />
                        <x-admin.field name="deadline_at" type="date" :label="__('custom_orders.form.deadline')" :value="old('deadline_at')" />
                    </div>
                    <label class="mt-4 flex items-start gap-3 rounded-2xl border border-white/10 bg-zinc-950/50 p-4">
                        <input type="hidden" name="budget_is_negotiable" value="0">
                        <input type="checkbox" name="budget_is_negotiable" value="1" @checked(old('budget_is_negotiable', true)) class="mt-1 rounded border-white/20 bg-zinc-950 text-emerald-400">
                        <span>
                            <span class="block text-sm font-bold text-white">{{ __('custom_orders.form.negotiable') }}</span>
                            <span class="mt-1 block text-xs text-zinc-500">{{ __('custom_orders.form.negotiable_helper') }}</span>
                        </span>
                    </label>
                </div>

                <div x-show="isPrint" x-cloak x-transition style="display: none;" class="rounded-3xl border border-white/10 bg-white/[0.04] p-6">
                    <h2 class="text-xl font-black text-white">{{ __('custom_orders.form.print_settings') }}</h2>
                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <x-admin.field name="quantity" type="number" min="1" :label="__('custom_orders.form.quantity')" :value="old('quantity')" x-bind:required="isPrint" />
                        <x-admin.field name="dimensions" :label="__('custom_orders.form.dimensions')" :value="old('dimensions')" :placeholder="__('custom_orders.form.dimensions_placeholder')" />
                        <x-admin.field name="material" :label="__('custom_orders.form.material')" :value="old('material')" placeholder="PLA, PETG, resin..." />
                        <x-admin.field name="color" :label="__('custom_orders.form.color')" :value="old('color')" />
                    </div>
                </div>

                <div class="rounded-3xl border border-dashed border-emerald-300/25 bg-emerald-300/[0.05] p-6">
                    <h2 class="text-xl font-black text-white">{{ __('custom_orders.form.files_refs') }}</h2>
                    <p class="mt-2 text-sm text-zinc-400" x-text="isPrint ? @js(__('custom_orders.form.files_helper_print')) : @js(__('custom_orders.form.files_helper_model'))"></p>
                    <input type="file" name="files[]" multiple class="mt-5 block w-full rounded-2xl border border-white/10 bg-zinc-950/70 px-4 py-4 text-sm text-zinc-300 file:mr-4 file:rounded-xl file:border-0 file:bg-emerald-400 file:px-4 file:py-2 file:text-sm file:font-black file:text-zinc-950">
                </div>
            </div>

            <aside class="self-start rounded-3xl border border-white/10 bg-zinc-950/70 p-5 shadow-2xl shadow-black/30 lg:sticky lg:top-28">
                <p class="text-sm font-black text-white">{{ __('custom_orders.form.safe_process') }}</p>
                <div class="mt-4 grid gap-3 text-sm text-zinc-400">
                    <p>{{ __('custom_orders.form.safe_terms') }}</p>
                    <p>{{ __('custom_orders.form.safe_escrow') }}</p>
                    <p>{{ __('custom_orders.form.safe_release') }}</p>
                    <p>{{ __('custom_orders.form.safe_dispute') }}</p>
                </div>
                @if($author)
                    <div class="mt-5 rounded-2xl border border-white/10 bg-white/[0.04] p-4">
                        <p class="text-xs text-zinc-500">{{ __('custom_orders.form.author') }}</p>
                        <p class="mt-1 font-black text-white">{{ $author->displayName() }}</p>
                    </div>
                @endif
                <button class="mt-5 h-12 w-full rounded-2xl bg-emerald-400 text-sm font-black text-zinc-950 shadow-lg shadow-emerald-500/20 hover:bg-emerald-300">{{ __('custom_orders.new_order') }}</button>
            </aside>
        </form>
    </section>
</x-layouts.marketplace>
