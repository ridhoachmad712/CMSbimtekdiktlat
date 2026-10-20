<div>
    @if ($registration)
        <div class="card p-6 text-center sm:p-10" role="status">
            <div class="mx-auto grid size-14 place-items-center rounded-full bg-green-100 text-green-700">
                <svg class="size-7" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-8 8a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.4L8 12.58l7.3-7.3a1 1 0 0 1 1.4 0z" clip-rule="evenodd"/></svg>
            </div>
            <h2 class="mt-4 text-2xl font-bold text-slate-900">Pendaftaran berhasil dikirim</h2>
            <p class="mt-2 text-slate-600">Simpan kode pendaftaran Anda:</p>
            <p class="mt-3 inline-block rounded-lg bg-brand-50 px-5 py-3 font-mono text-2xl font-bold tracking-wider text-brand-800">{{ $registration->code }}</p>

            <dl class="mx-auto mt-6 grid max-w-md gap-2 text-left text-sm">
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Nama</dt><dd class="text-right font-medium">{{ $registration->contact_name }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Instansi</dt><dd class="text-right font-medium">{{ $registration->institution }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Materi</dt><dd class="text-right font-medium">{{ $registration->topic?->title }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Jadwal</dt><dd class="text-right font-medium">{{ $registration->schedule->label }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Peserta</dt><dd class="text-right font-medium">{{ $registration->participants_count }} orang</dd></div>
            </dl>

            <p class="mt-6 text-sm text-slate-600">Lanjutkan konfirmasi ke admin kami melalui WhatsApp agar pendaftaran segera diproses.</p>
            <a href="{{ $whatsAppUrl }}" target="_blank" rel="noopener" class="btn btn-wa mt-4 px-6 py-3 text-base">Lanjutkan ke WhatsApp</a>
        </div>
    @else
        <form wire:submit="submit" class="card space-y-8 p-6 sm:p-8" novalidate>
            @error('form')
                <div class="rounded-lg bg-red-50 p-4 text-sm text-red-700" role="alert">{{ $message }}</div>
            @enderror

            {{-- Honeypot --}}
            <div class="hidden" aria-hidden="true">
                <label>Website <input type="text" wire:model="website" tabindex="-1" autocomplete="off"></label>
            </div>

            <fieldset class="space-y-4">
                <legend class="text-lg font-semibold text-slate-900">1. Pilihan Bimtek</legend>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="city_id" class="form-label">Kota <span class="text-red-600">*</span></label>
                        <select id="city_id" wire:model.live="city_id" class="form-input" required>
                            <option value="">— Pilih kota —</option>
                            @foreach ($this->cities as $city)
                                <option value="{{ $city->id }}">{{ $city->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="schedule_id" class="form-label">Tanggal <span class="text-red-600">*</span></label>
                        <select id="schedule_id" wire:model.live="schedule_id" class="form-input" @disabled(! $city_id) required>
                            <option value="">{{ $city_id ? '— Pilih tanggal —' : 'Pilih kota dahulu' }}</option>
                            @foreach ($this->schedules as $schedule)
                                <option value="{{ $schedule->id }}">{{ $schedule->date_range }} · {{ $schedule->mode->getLabel() }}{{ $schedule->topic ? ' · '.$schedule->topic->title : '' }}</option>
                            @endforeach
                        </select>
                        @error('schedule_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="category_id" class="form-label">Kategori Materi</label>
                        <select id="category_id" wire:model.live="category_id" class="form-input">
                            <option value="">— Pilih kategori —</option>
                            @foreach ($this->categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="topic_id" class="form-label">Materi <span class="text-red-600">*</span></label>
                        <select id="topic_id" wire:model="topic_id" class="form-input" @disabled(! $category_id) required>
                            <option value="">{{ $category_id ? '— Pilih materi —' : 'Pilih kategori dahulu' }}</option>
                            @foreach ($this->topics as $topic)
                                <option value="{{ $topic->id }}">{{ $topic->title }}</option>
                            @endforeach
                        </select>
                        @error('topic_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="package_id" class="form-label">Paket</label>
                        <select id="package_id" wire:model="package_id" class="form-input">
                            <option value="">— Belum menentukan —</option>
                            @foreach ($this->packages as $package)
                                <option value="{{ $package->id }}">{{ $package->name }}{{ $package->hasPrice() ? ' — '.$package->formatted_price : '' }}</option>
                            @endforeach
                        </select>
                        @error('package_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="participants_count" class="form-label">Jumlah Peserta <span class="text-red-600">*</span></label>
                        <input id="participants_count" type="number" min="1" max="500" wire:model="participants_count" class="form-input" required>
                        @error('participants_count') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </fieldset>

            <fieldset class="space-y-4">
                <legend class="text-lg font-semibold text-slate-900">2. Data Pendaftar</legend>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="contact_name" class="form-label">Nama Lengkap <span class="text-red-600">*</span></label>
                        <input id="contact_name" type="text" wire:model="contact_name" class="form-input" autocomplete="name" required>
                        @error('contact_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="phone" class="form-label">No. HP / WhatsApp <span class="text-red-600">*</span></label>
                        <input id="phone" type="tel" wire:model="phone" class="form-input" placeholder="08xxxxxxxxxx" autocomplete="tel" required>
                        @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="email" class="form-label">Email</label>
                        <input id="email" type="email" wire:model="email" class="form-input" autocomplete="email">
                        @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="position" class="form-label">Jabatan</label>
                        <input id="position" type="text" wire:model="position" class="form-input">
                        @error('position') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="institution" class="form-label">Instansi / OPD <span class="text-red-600">*</span></label>
                        <input id="institution" type="text" wire:model="institution" class="form-input" placeholder="mis. BPKAD Kab. Contoh" required>
                        @error('institution') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="region" class="form-label">Kabupaten/Kota/Provinsi</label>
                        <input id="region" type="text" wire:model="region" class="form-input">
                        @error('region') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="notes" class="form-label">Catatan</label>
                    <textarea id="notes" wire:model="notes" rows="3" class="form-input" placeholder="Nama peserta lain, permintaan khusus, dll."></textarea>
                    @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </fieldset>

            <div class="flex flex-col gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-slate-500">Data Anda hanya digunakan untuk keperluan pendaftaran Bimtek.</p>
                <button type="submit" class="btn btn-primary px-6 py-3" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="submit">Kirim Pendaftaran</span>
                    <span wire:loading wire:target="submit">Mengirim…</span>
                </button>
            </div>
        </form>
    @endif
</div>
