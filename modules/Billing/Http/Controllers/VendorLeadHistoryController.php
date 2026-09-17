<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use ModulesShoppingComplex\Analytics\Enums\ViewSourceEnum;
use ModulesShoppingComplex\Billing\Enums\BillableLeadStateEnum;
use ModulesShoppingComplex\Billing\Models\BillableLead;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VendorLeadHistoryController extends Controller
{
    private const STATES = ['billed', 'repeat', 'unbilled', 'credited'];

    public function index(Request $request): InertiaResponse|RedirectResponse
    {
        if ($redirect = $this->denyNonVendor()) {
            return $redirect;
        }

        $leads = $this->filtered($request)
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through($this->present(...));

        return Inertia::render('Vendor/Leads', [
            'leads' => $leads,
            'states' => self::STATES,
            'filters' => [
                'state' => $request->query('state', ''),
                'from' => $request->query('from', ''),
                'to' => $request->query('to', ''),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse|RedirectResponse
    {
        if ($redirect = $this->denyNonVendor()) {
            return $redirect;
        }

        $query = $this->filtered($request)->orderByDesc('id');

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Searched for', 'Buyer area', 'Channel', 'Coins charged', 'State', 'Repeat contacts']);

            $query->chunk(500, function ($leads) use ($out): void {
                foreach ($leads as $lead) {
                    fputcsv($out, [
                        $lead->created_at->toDateTimeString(),
                        $this->csvSafe($lead->buyer_search),
                        $this->csvSafe($lead->buyer_area),
                        $this->channel($lead),
                        $lead->coins_charged,
                        $this->stateLabel($lead),
                        $lead->repeat_count,
                    ]);
                }
            });

            fclose($out);
        }, 'leads-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * @return Builder<BillableLead>
     */
    private function filtered(Request $request): Builder
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'state' => ['nullable', 'in:'.implode(',', self::STATES)],
        ]);

        $query = BillableLead::where('vendor_id', Auth::id());

        if (($from = $request->query('from')) !== null && $from !== '') {
            $query->where('created_at', '>=', Carbon::parse((string) $from)->startOfDay());
        }

        if (($to = $request->query('to')) !== null && $to !== '') {
            $query->where('created_at', '<=', Carbon::parse((string) $to)->endOfDay());
        }

        match ($request->query('state')) {
            'billed' => $query->where('state', BillableLeadStateEnum::CHARGED)->whereNull('credit_reason'),
            'credited' => $query->whereNotNull('credit_reason'),
            'unbilled' => $query->where('state', BillableLeadStateEnum::UNBILLED),
            'repeat' => $query->where('repeat_count', '>', 0),
            default => null,
        };

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    private function present(BillableLead $lead): array
    {
        return [
            'id' => $lead->id,
            'date' => $lead->created_at->toIso8601String(),
            'search' => $lead->buyer_search,
            'area' => $lead->buyer_area,
            'channel' => $this->channel($lead),
            'coins_charged' => $lead->coins_charged,
            'state' => $this->stateLabel($lead),
            'unbilled_reason' => $lead->unbilled_reason?->value,
            'credit_reason' => $lead->credit_reason?->value,
            'repeat_count' => $lead->repeat_count,
        ];
    }

    private function stateLabel(BillableLead $lead): string
    {
        return match (true) {
            $lead->credit_reason !== null => 'credited',
            $lead->state === BillableLeadStateEnum::CHARGED => 'billed',
            default => 'unbilled',
        };
    }

    private function channel(BillableLead $lead): string
    {
        return $lead->channel === ViewSourceEnum::WHATSAPP ? 'bot' : 'web';
    }

    /**
     * Neutralise spreadsheet formula injection: a buyer-typed search that starts
     * with =, +, -, @ or a control char must not execute when the vendor opens
     * the CSV in Excel or Sheets.
     */
    private function csvSafe(?string $value): string
    {
        $value = (string) $value;

        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }

    private function denyNonVendor(): ?RedirectResponse
    {
        if (Auth::user()->role !== 'vendor') {
            return redirect()->route('home')->with('error', 'Only vendors can view leads.');
        }

        return null;
    }
}
