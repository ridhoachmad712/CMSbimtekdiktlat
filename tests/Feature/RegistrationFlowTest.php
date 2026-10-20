<?php

namespace Tests\Feature;

use App\Enums\RegistrationSource;
use App\Enums\RegistrationStatus;
use App\Enums\ScheduleStatus;
use App\Livewire\RegistrationForm;
use App\Models\Package;
use App\Models\Registration;
use App\Models\Schedule;
use App\Models\Topic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\TestCase;

class RegistrationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        RateLimiter::clear('registration:127.0.0.1');
    }

    private function fillValid($component, Schedule $schedule, Topic $topic)
    {
        return $component
            ->set('schedule_id', (string) $schedule->id)
            ->set('category_id', (string) $topic->category_id)
            ->set('topic_id', (string) $topic->id)
            ->set('contact_name', 'Budi Santoso')
            ->set('phone', '0812-3456-7890')
            ->set('email', 'budi@example.com')
            ->set('institution', 'BPKAD Kab. Contoh')
            ->set('position', 'Kasubbag Keuangan')
            ->set('region', 'Kab. Contoh, Sulawesi Selatan')
            ->set('participants_count', 3);
    }

    public function test_register_page_renders_form(): void
    {
        $this->get('/daftar')
            ->assertOk()
            ->assertSeeLivewire(RegistrationForm::class)
            ->assertSee('Kirim Pendaftaran')
            ->assertSee(Schedule::upcoming()->with('city')->first()->city->name)
            ->assertSee('Pilih kota dahulu');
    }

    public function test_choosing_city_lists_only_its_schedules(): void
    {
        $schedule = Schedule::upcoming()->with('city')->first();
        $other = Schedule::upcoming()->where('city_id', '!=', $schedule->city_id)->first();

        Livewire::test(RegistrationForm::class)
            ->assertDontSee($schedule->date_range.' · ')
            ->set('city_id', (string) $schedule->city_id)
            ->assertSee($schedule->date_range.' · '.$schedule->mode->getLabel())
            ->set('schedule_id', (string) $schedule->id)
            // Ganti kota: jadwal kota sebelumnya dikosongkan.
            ->set('city_id', (string) $other->city_id)
            ->assertSet('schedule_id', '');

        // Memilih jadwal langsung (mis. dari prefill) ikut menyetel kotanya.
        Livewire::test(RegistrationForm::class)
            ->set('schedule_id', (string) $schedule->id)
            ->assertSet('city_id', (string) $schedule->city_id);
    }

    public function test_register_page_prefills_from_query_string(): void
    {
        $schedule = Schedule::upcoming()->first();
        $topic = Topic::first();
        $package = Package::first();

        Livewire::withQueryParams(['jadwal' => $schedule->id, 'materi' => $topic->slug, 'paket' => $package->id])
            ->test(RegistrationForm::class)
            ->assertSet('schedule_id', (string) $schedule->id)
            ->assertSet('city_id', (string) $schedule->city_id)
            ->assertSet('category_id', (string) $topic->category_id)
            ->assertSet('topic_id', (string) $topic->id)
            ->assertSet('package_id', (string) $package->id);
    }

    public function test_successful_registration_is_saved_and_shows_code_and_whatsapp_button(): void
    {
        $schedule = Schedule::upcoming()->with('city')->first();
        $topic = Topic::first();

        $component = $this->fillValid(Livewire::test(RegistrationForm::class), $schedule, $topic)
            ->set('package_id', (string) Package::first()->id)
            ->call('submit')
            ->assertHasNoErrors();

        $registration = Registration::sole();
        $this->assertSame(RegistrationSource::Web, $registration->source);
        $this->assertSame(RegistrationStatus::New, $registration->status);
        $this->assertSame($schedule->id, $registration->schedule_id);
        $this->assertSame($topic->id, $registration->topic_id);
        $this->assertSame('Budi Santoso', $registration->contact_name);
        $this->assertSame(3, $registration->participants_count);
        $this->assertNull($registration->notes);

        $component
            ->assertSet('registrationId', $registration->id)
            ->assertSee('Pendaftaran berhasil dikirim')
            ->assertSee($registration->code)
            ->assertSee('Lanjutkan ke WhatsApp')
            ->assertSee('https://wa.me/6281200000000?text=', false)
            ->assertSee(rawurlencode('Kode: '.$registration->code), false)
            ->assertSee(rawurlencode('Materi: '.$topic->title), false);
    }

    public function test_required_fields_are_validated(): void
    {
        Livewire::test(RegistrationForm::class)
            ->set('participants_count', 0)
            ->call('submit')
            ->assertHasErrors(['schedule_id', 'topic_id', 'contact_name', 'phone', 'institution', 'participants_count'])
            ->assertSee('Nama wajib diisi.');

        $this->assertSame(0, Registration::count());
    }

    public function test_invalid_phone_and_closed_schedule_are_rejected(): void
    {
        $closed = Schedule::upcoming()->first();
        $closed->update(['status' => ScheduleStatus::Closed]);

        $this->fillValid(Livewire::test(RegistrationForm::class), $closed, Topic::first())
            ->set('phone', 'abc')
            ->call('submit')
            ->assertHasErrors(['schedule_id', 'phone']);

        $this->assertSame(0, Registration::count());
    }

    public function test_honeypot_blocks_bots_silently(): void
    {
        $this->fillValid(Livewire::test(RegistrationForm::class), Schedule::upcoming()->first(), Topic::first())
            ->set('website', 'http://spam.example')
            ->call('submit')
            ->assertSet('registrationId', null);

        $this->assertSame(0, Registration::count());
    }

    public function test_registration_id_cannot_be_tampered_from_client(): void
    {
        $this->expectException(CannotUpdateLockedPropertyException::class);

        Livewire::test(RegistrationForm::class)->set('registrationId', 1);
    }

    public function test_submissions_are_rate_limited(): void
    {
        $schedule = Schedule::upcoming()->first();
        $topic = Topic::first();

        foreach (range(1, 5) as $i) {
            $this->fillValid(Livewire::test(RegistrationForm::class), $schedule, $topic)->call('submit')->assertHasNoErrors();
        }

        $this->fillValid(Livewire::test(RegistrationForm::class), $schedule, $topic)
            ->call('submit')
            ->assertHasErrors('form');

        $this->assertSame(5, Registration::count());
    }

    public function test_choosing_category_resets_topic(): void
    {
        $topic = Topic::first();
        $other = Topic::where('category_id', '!=', $topic->category_id)->first();

        Livewire::test(RegistrationForm::class)
            ->set('category_id', (string) $topic->category_id)
            ->set('topic_id', (string) $topic->id)
            ->set('category_id', (string) $other->category_id)
            ->assertSet('topic_id', '')
            ->assertSee($other->title);
    }
}
