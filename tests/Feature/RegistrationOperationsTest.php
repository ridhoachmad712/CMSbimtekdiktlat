<?php

namespace Tests\Feature;

use App\Enums\RegistrationSource;
use App\Enums\RegistrationStatus;
use App\Enums\UserRole;
use App\Filament\Exports\RegistrationExporter;
use App\Filament\Resources\Registrations\Pages\EditRegistration;
use App\Filament\Resources\Registrations\Pages\ListRegistrations;
use App\Models\Package;
use App\Models\Registration;
use App\Models\Schedule;
use App\Models\User;
use App\Services\InvoicePdf;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class RegistrationOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function registration(array $attributes = []): Registration
    {
        return Registration::create([
            'schedule_id' => Schedule::first()->id,
            'package_id' => Package::first()->id,
            'contact_name' => 'Budi Santoso',
            'phone' => '081234567890',
            'institution' => 'Dinas Kominfo',
            'participants_count' => 3,
            'source' => RegistrationSource::Manual,
            ...$attributes,
        ]);
    }

    public function test_bulk_change_status(): void
    {
        $this->actingAs(User::factory()->role(UserRole::Cs)->create());
        $records = collect([$this->registration(), $this->registration()]);

        Livewire::test(ListRegistrations::class)
            ->callTableBulkAction('changeStatus', $records, data: ['status' => RegistrationStatus::Confirmed->value])
            ->assertHasNoTableBulkActionErrors()
            ->assertNotified('2 pendaftaran diperbarui');

        $records->each(fn ($r) => $this->assertSame(RegistrationStatus::Confirmed, $r->fresh()->status));
    }

    public function test_export_action_creates_completed_export(): void
    {
        Storage::fake('local');
        $this->actingAs(User::first());
        $this->registration();
        $this->registration(['contact_name' => '=HYPERLINK("http://evil")']);

        Livewire::test(ListRegistrations::class)
            ->callAction('export')
            ->assertHasNoActionErrors();

        $export = Export::sole();
        $this->assertSame(RegistrationExporter::class, $export->exporter);
        $this->assertSame(2, $export->total_rows);
        $this->assertSame(2, $export->successful_rows);
        $this->assertNotNull($export->completed_at);

        $csv = collect(Storage::disk('local')->allFiles("filament_exports/{$export->id}"))
            ->filter(fn ($f) => str_ends_with($f, '.csv'))
            ->map(fn ($f) => Storage::disk('local')->get($f))
            ->implode('');
        $this->assertStringContainsString('Kode', $csv);
        $this->assertStringContainsString('Budi Santoso', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);
    }

    public function test_exporter_columns(): void
    {
        $names = collect(RegistrationExporter::getColumns())->map->getName();

        $this->assertTrue($names->contains('code'));
        $this->assertTrue($names->contains('schedule.city.name'));
        $this->assertTrue($names->contains('topic.title'));
    }

    public function test_invoice_pdf_is_rendered(): void
    {
        $registration = $this->registration();
        $invoice = app(InvoicePdf::class);

        $this->assertStringStartsWith('%PDF', $invoice->render($registration));
        $this->assertSame('invoice-'.strtolower($registration->code).'.pdf', $invoice->fileName($registration));

        $registration->update(['status' => RegistrationStatus::Paid]);
        $this->assertSame('kuitansi-'.strtolower($registration->code).'.pdf', $invoice->fileName($registration->fresh()));
    }

    public function test_invoice_download_action(): void
    {
        $this->actingAs(User::factory()->role(UserRole::Cs)->create());
        $registration = $this->registration();

        Livewire::test(EditRegistration::class, ['record' => $registration->getRouteKey()])
            ->callAction('invoice')
            ->assertFileDownloaded(app(InvoicePdf::class)->fileName($registration));

        Livewire::test(ListRegistrations::class)
            ->assertTableActionExists('invoice');
    }

    public function test_invoice_total_uses_package_price(): void
    {
        $registration = $this->registration();
        $html = view('pdf.invoice', [
            'registration' => $registration->load(['schedule.city', 'participants']),
            'isReceipt' => false,
            'unitPrice' => 5_000_000,
            'quantity' => 3,
            'total' => 15_000_000,
            'site' => ['name' => 'Bimtek Diklat', 'address' => null, 'phone' => null, 'email' => null, 'whatsapp' => null],
        ])->render();

        $this->assertStringContainsString('INVOICE', $html);
        $this->assertStringContainsString('Rp 15.000.000', $html);
        $this->assertStringContainsString('Dinas Kominfo', $html);
    }
}
