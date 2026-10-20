<?php

namespace App\Livewire;

use App\Enums\RegistrationSource;
use App\Enums\RegistrationStatus;
use App\Enums\ScheduleStatus;
use App\Models\Category;
use App\Models\City;
use App\Models\Package;
use App\Models\Registration;
use App\Models\Schedule;
use App\Models\Topic;
use App\Support\WhatsApp;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class RegistrationForm extends Component
{
    /** Hanya penyaring pilihan jadwal; tidak disimpan. */
    public string $city_id = '';

    public string $schedule_id = '';

    public string $category_id = '';

    public string $topic_id = '';

    public string $package_id = '';

    public string $contact_name = '';

    public string $phone = '';

    public string $email = '';

    public string $institution = '';

    public string $position = '';

    public string $region = '';

    public int|string $participants_count = 1;

    public string $notes = '';

    /** Honeypot anti-spam: harus tetap kosong. */
    public string $website = '';

    #[Locked]
    public ?int $registrationId = null;

    /** Prefill dari query string: ?jadwal={id}&materi={slug}&paket={id} */
    public function mount(): void
    {
        $schedule = Schedule::upcoming()->find(request()->integer('jadwal'));
        if ($schedule) {
            $this->schedule_id = (string) $schedule->id;
            $this->city_id = (string) $schedule->city_id;
            if ($schedule->topic_id) {
                $this->selectTopic($schedule->topic);
            }
        }

        if ($slug = request()->query('materi')) {
            $this->selectTopic(Topic::active()->where('slug', $slug)->first());
        }

        if ($package = Package::active()->find(request()->integer('paket'))) {
            $this->package_id = (string) $package->id;
        }
    }

    public function updatedCategoryId(): void
    {
        $this->topic_id = '';
    }

    public function updatedCityId(): void
    {
        if ($this->selectedSchedule?->city_id !== (int) $this->city_id) {
            $this->schedule_id = '';
        }
    }

    public function updatedScheduleId(): void
    {
        $schedule = $this->selectedSchedule;
        if ($schedule) {
            $this->city_id = (string) $schedule->city_id;
        }
        if ($schedule?->topic) {
            $this->selectTopic($schedule->topic);
        }
    }

    /** Kota yang punya jadwal terbuka ke depan. */
    #[Computed]
    public function cities()
    {
        return City::query()
            ->whereIn('id', Schedule::upcoming()->reorder()->select('city_id'))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function schedules()
    {
        return $this->city_id
            ? Schedule::upcoming()->where('city_id', $this->city_id)->with('topic')->limit(100)->get()
            : collect();
    }

    #[Computed]
    public function categories()
    {
        return Category::active()->hasActiveTopics()->get(['id', 'name']);
    }

    #[Computed]
    public function topics()
    {
        return $this->category_id
            ? Topic::active()->where('category_id', $this->category_id)->orderBy('title')->get(['id', 'title'])
            : collect();
    }

    #[Computed]
    public function packages()
    {
        return Package::active()->get();
    }

    #[Computed]
    public function selectedSchedule(): ?Schedule
    {
        return $this->schedule_id ? Schedule::with(['city', 'topic'])->find($this->schedule_id) : null;
    }

    #[Computed]
    public function registration(): ?Registration
    {
        return $this->registrationId
            ? Registration::with(['schedule.city', 'topic', 'package'])->find($this->registrationId)
            : null;
    }

    protected function rules(): array
    {
        return [
            'schedule_id' => ['required', Rule::exists('schedules', 'id')->where('status', ScheduleStatus::Open->value)->where(fn ($q) => $q->whereDate('start_date', '>=', today()))],
            'category_id' => ['nullable', Rule::exists('categories', 'id')->where('is_active', true)],
            'topic_id' => ['required', Rule::exists('topics', 'id')->where('is_active', true)],
            'package_id' => ['nullable', Rule::exists('packages', 'id')->where('is_active', true)],
            'contact_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9\s\-]{9,20}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'institution' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:255'],
            'participants_count' => ['required', 'integer', 'min:1', 'max:500'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'schedule_id' => 'jadwal',
            'topic_id' => 'materi',
            'package_id' => 'paket',
            'contact_name' => 'nama',
            'phone' => 'nomor HP/WhatsApp',
            'institution' => 'instansi',
            'position' => 'jabatan',
            'region' => 'kabupaten/kota/provinsi',
            'participants_count' => 'jumlah peserta',
            'notes' => 'catatan',
        ];
    }

    protected function messages(): array
    {
        return [
            'required' => ':Attribute wajib diisi.',
            'exists' => ':Attribute yang dipilih tidak tersedia.',
            'email' => 'Format :attribute tidak valid.',
            'phone.regex' => 'Nomor HP/WhatsApp tidak valid.',
            'integer' => ':Attribute harus berupa angka.',
            'min' => ':Attribute minimal :min.',
            'max' => ':Attribute maksimal :max.',
        ];
    }

    public function submit(): void
    {
        if (filled($this->website)) {
            return;
        }

        $data = $this->validate();

        $key = 'registration:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('form', 'Terlalu banyak percobaan. Silakan coba lagi dalam beberapa menit atau hubungi kami via WhatsApp.');

            return;
        }
        RateLimiter::hit($key, 600);

        $registration = Registration::create([
            ...collect($data)->except('category_id')->map(fn ($v) => $v === '' ? null : $v)->all(),
            'status' => RegistrationStatus::New,
            'source' => RegistrationSource::Web,
        ]);

        $this->registrationId = $registration->id;
    }

    public function whatsAppMessage(Registration $registration): string
    {
        return implode("\n", array_filter([
            'Halo, saya sudah mendaftar Bimtek melalui website.',
            '',
            'Kode: '.$registration->code,
            'Nama: '.$registration->contact_name,
            'Instansi: '.$registration->institution,
            'Materi: '.$registration->topic?->title,
            'Jadwal: '.$registration->schedule->label,
            $registration->package ? 'Paket: '.$registration->package->name : null,
            'Jumlah peserta: '.$registration->participants_count,
            '',
            'Mohon konfirmasinya. Terima kasih.',
        ], fn ($line) => $line !== null));
    }

    public function render(): View
    {
        $registration = $this->registration;

        return view('livewire.registration-form', [
            'registration' => $registration,
            'whatsAppUrl' => $registration ? WhatsApp::link($this->whatsAppMessage($registration)) : null,
        ]);
    }

    private function selectTopic(?Topic $topic): void
    {
        if ($topic?->is_active) {
            $this->category_id = (string) $topic->category_id;
            $this->topic_id = (string) $topic->id;
        }
    }
}
