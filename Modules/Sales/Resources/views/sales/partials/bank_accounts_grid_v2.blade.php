@if (isset($bankAccounts) && count($bankAccounts) > 0)
    <div class="section">
        <div class="section-title">Cuentas bancarias</div>
        <table style="width: 100%">
            <tbody>
                <tr>
                    @php $columnCount = 0; @endphp
                    @foreach ($bankAccounts as $account)
                        <td class="bank-cell">
                            <div class="bank-card">
                                <table style="width: 100%">
                                    <tr>
                                        <td class="bank-logo-cell">
                                            @if (!empty($account->bank?->image))
                                                <img src="{{ public_path($account->bank->image) }}" class="bank-logo">
                                            @endif
                                        </td>
                                        <td>
                                            <div class="bank-title">{{ $account->description }}</div>
                                            <div class="bank-subtitle">{{ $account->bank->full_name }}</div>
                                        </td>
                                    </tr>
                                </table>
                                <div class="bank-detail"><strong>Número de cuenta:</strong> {{ $account->number }}</div>
                                <div class="bank-detail"><strong>CCI:</strong> {{ $account->cci }}</div>
                            </div>
                        </td>
                        @php $columnCount++; @endphp
                        @if ($columnCount % 2 === 0 && ! $loop->last)
                            </tr><tr>
                        @endif
                    @endforeach
                    @if ($columnCount % 2 !== 0)
                        <td class="bank-cell"></td>
                    @endif
                </tr>
            </tbody>
        </table>
    </div>
@endif
