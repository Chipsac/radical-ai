<x-app-layout>
    <x-slot name="title">Payslips</x-slot>

    <div class="mx-auto max-w-4xl">
        <div class="mb-6">
            <h1 class="font-display text-3xl font-semibold text-gray-900 dark:text-white">Payslips</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ auth()->user()->isAdmin() ? 'Upload payslips for any employee. Figures are uploaded, not calculated.' : 'Your payslips only — nobody else can see them.' }}
            </p>
        </div>

        <x-help-banner id="tip:payslips"
            title="Payslips are private by default"
            text="Only an admin can upload. Everyone else sees and downloads their own payslips and nobody else's — that rule is enforced on the server for every single download, not just hidden in the interface." />

        @if (auth()->user()->isAdmin())
            <div class="card mb-6 p-6">
                <h2 class="flex items-center gap-1.5 font-display text-lg font-semibold">
                    Upload payslip
                    <x-help-tip title="No tax calculation"
                        text="Radical AI distributes payslips rather than calculating them. Upload the PDF from your payroll provider along with the period and gross/net figures." />
                </h2>
                <form method="POST" action="{{ route('hr.payslips.store') }}" enctype="multipart/form-data" class="mt-4 grid gap-4 sm:grid-cols-2">
                    @csrf
                    <div>
                        <label class="label-app">Employee *</label>
                        <select name="employee_id" required class="input-app">
                            @foreach ($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->user?->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label-app">Period *</label>
                        <input name="period_label" required class="input-app" placeholder="August 2026" />
                    </div>
                    <div>
                        <label class="label-app">Pay date *</label>
                        <input type="date" name="pay_date" required class="input-app" value="{{ now()->toDateString() }}" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="label-app">Gross (€) *</label>
                            <input type="number" step="0.01" min="0" name="gross" required class="input-app" />
                        </div>
                        <div>
                            <label class="label-app">Net (€) *</label>
                            <input type="number" step="0.01" min="0" name="net" required class="input-app" />
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label-app">PDF file *</label>
                        <input type="file" name="file" accept="application/pdf" required
                               class="block w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-gold file:px-3 file:py-2 file:text-sm file:font-semibold file:text-ink-950 hover:file:bg-gold-dark dark:text-gray-400" />
                        @error('file') <p class="mt-1 text-xs text-priority-high">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2 flex justify-end">
                        <button type="submit" class="btn-gold">Upload payslip</button>
                    </div>
                </form>
            </div>
        @endif

        <div class="card overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-xs uppercase tracking-wide text-gray-500 dark:border-ink-700 dark:text-gray-400">
                        @if (auth()->user()->isAdmin())<th class="px-5 py-3">Employee</th>@endif
                        <th class="px-5 py-3">Period</th>
                        <th class="px-5 py-3">Pay date</th>
                        <th class="px-5 py-3 text-right">Gross</th>
                        <th class="px-5 py-3 text-right">Net</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-ink-700">
                    @forelse ($payslips as $payslip)
                        <tr class="transition hover:bg-gray-50 dark:hover:bg-ink-800">
                            @if (auth()->user()->isAdmin())
                                <td class="px-5 py-3 font-medium">{{ $payslip->employee?->user?->name }}</td>
                            @endif
                            <td class="px-5 py-3 font-medium">{{ $payslip->period_label }}</td>
                            <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ $payslip->pay_date->format('j M Y') }}</td>
                            <td class="px-5 py-3 text-right">€{{ number_format($payslip->gross, 2) }}</td>
                            <td class="px-5 py-3 text-right font-semibold">€{{ number_format($payslip->net, 2) }}</td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('hr.payslips.download', $payslip) }}" class="font-medium text-gold hover:underline">Download</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-gray-500">No payslips yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
